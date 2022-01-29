<?php

declare(strict_types = 1);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: *');

try {
    $DB_CONNECTION = new PDO('sqlite:/data/hunter.db');
    $DB_CONNECTION->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    print_message('Error connecting to the database'.$e->getMessage(), 500);
}

$command = $_GET['command'];

if (!$command) {
    print_message('Command not found', 400);
    return;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($command === 'dump') {
        // This commands returns everything in the db in this format
        // {
        //    'ticker': {
        //        ...iposColumns
        //        ...companiesColumns
        //        'earnings': [],
        //    }
        // }

        $response = [];

        // Get base IPO data
        $query = $DB_CONNECTION->prepare('SELECT * FROM ipos;');
        $query->execute();
        $results = $query->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($results as $ipo) {
            $response[$ipo['symbol']] = $ipo;
        }

        // Add companies info
        $query = $DB_CONNECTION->prepare('SELECT * FROM companies;');
        $query->execute();
        $results = $query->fetchAll(PDO::FETCH_ASSOC);

        foreach ($results as $company) {
            $responseSymbol = $response[$company['symbol']] ?? [];
            if ($responseSymbol) {
                $response[$company['symbol']] = array_merge($company, $responseSymbol);
            }
        }

        // Add earnings info
        $query = $DB_CONNECTION->prepare('SELECT * FROM earnings;');
        $query->execute();
        $results = $query->fetchAll(PDO::FETCH_ASSOC);

        foreach($results as $earning) {
            $responseSymbol = $response[$earning['symbol']] ?? [];
            if ($responseSymbol) {
                if(isset($responseSymbol['earnings'])) {
                    $responseSymbol['earnings'].array_push($earning);
                } else {
                    $responseSymbol['earnings'] = [$earning];
                }
                $response[$earning['symbol']] = $responseSymbol;
            }
        }

        // Add analysis info
        // Get latest price
        $query = $DB_CONNECTION->prepare('SELECT symbol, close FROM historical ORDER BY id DESC LIMIT 1;');
        $query->execute();
        $results = $query->fetchAll(PDO::FETCH_ASSOC);

        foreach ($results as $res) {
            $responseSymbol = $response[$res['symbol']] ?? [];
            if ($responseSymbol) {
                $response[$res['symbol']] = array_merge($res['symbol'], $responseSymbol);
            }            
        }

        // Get all time results
        $query = $DB_CONNECTION->prepare("SELECT symbol, AVG(close) as 'average', MAX(close) as 'highest', MIN(close) as 'lowest' FROM historical WHERE interval = '1d' GROUP BY symbol;");
        $query->execute();
        $results = $query->fetchAll(PDO::FETCH_ASSOC);

        foreach ($results as $res) {
            $responseSymbol = $response[$res['symbol']] ?? [];
            if ($responseSymbol) {
                $response[$res['symbol']] = array_merge($res['symbol'], $responseSymbol);
            }
        }

        // Get last year results
        $query = $DB_CONNECTION->prepare("SELECT symbol, AVG(close) as 'average1Y', MAX(close) as 'highest1Y', MIN(close) as 'lowest1Y' FROM historical WHERE interval = '1d' AND DATE(datetime) > DATE('now', '-1 year') GROUP BY symbol;");
        $query->execute();
        $results = $query->fetchAll(PDO::FETCH_ASSOC);

        foreach ($results as $res) {
            $responseSymbol = $response[$res['symbol']] ?? [];
            if ($responseSymbol) {
                $response[$res['symbol']] = array_merge($res['symbol'], $responseSymbol);
            }
        }

        // Get last 6 months results
        $query = $DB_CONNECTION->prepare("SELECT symbol, AVG(close) as 'average6Mo', MAX(close) as 'highest6Mo', MIN(close) as 'lowest6Mo' FROM historical WHERE interval = '1d' AND DATE(datetime) > DATE('now', '-6 months') GROUP BY symbol;");
        $query->execute();
        $results = $query->fetchAll(PDO::FETCH_ASSOC);

        foreach ($results as $res) {
            $responseSymbol = $response[$res['symbol']] ?? [];
            if ($responseSymbol) {
                $response[$res['symbol']] = array_merge($res['symbol'], $responseSymbol);
            }
        }

        // Print json results
        print_message(json_encode($response));
    } else if ($command === 'list') {
        // Query the database
        $query = $DB_CONNECTION->prepare('SELECT i.symbol, i.offerDate, i.offerPrice, c.name, c.industry, c.sector FROM ipos as i LEFT JOIN companies as c ON i.symbol = c.symbol;');
        $query->execute();
        $results = $query->fetchAll(PDO::FETCH_ASSOC);

        // Echo results in json
        print_message(json_encode($results));
    } else {
        print_message('Unrecognized command', 400);
    }
}

function print_message($message, $code = 200) {
    header('Content-Type: application/json');
    http_response_code($code);
    echo $message;
}