<!DOCTYPE html>
<html lang="">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title></title>
    <link rel="stylesheet" href="">
</head>

<body>

     <?php
    
    /* Funkce 'connect_to_database()' zajistí připojení do databáze 'feo_database'. */
    function connect_to_database(){
        $connection = new mysqli("localhost", "root", "", "feo_database");

        if ($connection->connect_error) {
            die("Connection failed: " . $connection->connect_error);
        }
        return $connection;
    }
    
    /* Funkce 'get_current_date()' vrací nejbližší datum k tomu dnešnímu, které je nalezeno v databázi. Výchozí hodnota v proměnné '$current_date'
    je nastavena na rok 1900. Všechny řádky v databázi jsou postupně procházeny while cyklem. V případě nalezení bližšího data na řádku k tomu dnešnímu,
    než je uvedené v proměnné '$current_date', se hodnota v této proměnné přepíše právě nalezeným datem. */
    function get_current_date($result, $current_date){
        while ($row = mysqli_fetch_row($result)){
            $line_date = $row[4];
            if ($line_date > $current_date){
                $current_date = $line_date;
            }
        }
        return $current_date;
    }
    
    /* Funkce 'fetch_data()' vrací dvě asociativní pole, kde první obsahuje informace o uživatelích. Druhé pole obsahuje částky, které každý uživatel zaplatil,
    jak v czk, tak v eurech. Nejprve procházíme celou databázi while cyklem. Do proměnných (ř. 49 - ř. 52) si načteme hodnoty z databéze, které
    budeme později potřebovat. Jednou z proměnných je datum, které jsme uložili do proměnné '$date'. Tohle datum je porovnáváno s datem, které je
    o 1 měsíc starší ('$date_without_month'), než to, které nám vrátila funkce 'get_current_date()'. V případě, že je datum v proměnné '$date' bližší k dnešnímu, 
    než uložené datum v proměnné '$date_without_month' jsou do proměnné '$user_data' vkládáány informace o uživatelích (jméno, ulice, ..), které jsou každému 
    uživateli do pole '$users' vloženy jen jednou. Proměnná '$key' obsahuje id uživatele a měnu (např: 1_czk, 5_eur), která slouží jako klíč pro odkázání na sumu, 
    kterou každý uživatel zaplatil v obou měnách. Jesliže klíč v poli existuje, pak je do něj přičtena hodnota z proměnné '$price', která obsahuje cenu 
    aktuální transakce. V opačném případě je klíč vytvořen a je mu přidělena hodnota z proměnné '$price'. Jestliže aktuální transakce byla provedena v eurech, 
    pak se vytvoří klíč pro czk, což zajistí přítomnost obou klíčů každého uživatele. Do těchto klíčů je přiřazena částka 0, aby v případě neprovedení 
    žádné transakce uživatele v požadovaném časovém rozmezí nezůstala žádná buňka prázdná při pozdějším exportu do .csv souboru. */
    function fetch_data($result_2, $date_without_month, $users, $prices){
        while($row_2 = mysqli_fetch_row($result_2)){
            $id = $row_2[1];
            $price = $row_2[2];
            $currency = $row_2[3];
            $date = $row_2[4];
            if ($date >= $date_without_month){
                $user_data = [$row_2[1], $row_2[8], $row_2[9], $row_2[10], $row_2[11], $row_2[7]];
                if (!in_array($user_data, $users)){
                    array_push($users, $user_data);
                }
                $key = $id."_".$currency;
                if (array_key_exists($key, $prices)){
                    $prices[$key] += $price;
                } else {
                    $prices[$id."_".$currency] = $price;
                    if ($currency == "eur"){
                        $prices[$id."_czk"] = 0;
                    } else {
                        $prices[$id."_eur"] = 0;
                    }
                } 
            }
        }
        return [$users, $prices];
    }
    
    /* Funkce 'save_to_csv()' zajistí uložení dat do souboru s příponou '.csv'. Nejprve si nadefinujeme oddělovač, jméno souboeru a názvy všech sloupců.
    Tato data následně vložíme do našeho otevřeného souboru. Následně cyklem foreach projdeme pole '$users', kde každý prvek obsahuje data o uživateli ('$user').
    Tato data jsou postupně ukládána do souboru '.csv' společně s částkami o jednotlivých uživatelích. Na čátky odkazujeme pomocí dříve uvedených klíčů.
    ID každého uživatele je uvedeno na prvním (nultém) prvku v poli '$user', ke kterému přidáme zbývající část klíče ('_czk' a '_eur'). */
    function save_to_csv($users, $prices){
        $delimiter = ';';
        $filename = "users.csv";
        $f = fopen($filename, "w");
        $fields = array("ID", "NAME", "ADDRESS", "PHONE", "EMAIL", "PASSWORD", "PRICE OF ALL TRANSACTIONS (CZK)", "PRICE OF ALL TRANSACTIONS (EUR)");
        fputcsv($f, $fields, $delimiter);
        
        foreach ($users as $user){
            array_push($user, $prices[$user[0]."_czk"], $prices[$user[0]."_eur"]);
            fputcsv($f, $user, $delimiter);   
        }
    }
    
    $prices = [];
    $users = [];
    $current_date = "1900-01-01 00:00:00";    
    
    $sql = "SELECT * FROM transactions, users WHERE transactions.user_id = users.id";
    $result = mysqli_query(connect_to_database(), $sql);
    $result_2 = mysqli_query(connect_to_database(), $sql);
    if (!$result and !$result_2){
        die("The query is not valid.");
    }
    
    $current_date = get_current_date($result, $current_date);
    $date_without_month = date("Y-m-d", strtotime('-1 month', strtotime($current_date)));
    $fetched_data = fetch_data($result_2, $date_without_month, $users, $prices);
    save_to_csv($fetched_data[0], $fetched_data[1]);
?>

</body>
</html>
