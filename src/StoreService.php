<?php
namespace Sideco;

use \Sideco\PostgreSQL;
use \Sideco\Config;

class StoreService
{
    public static $instance;

    public static function instance()
    {
        if ( ! self::$instance ) self::$instance = new self;
        return self::$instance;
    }

    /**
    *
    */
    private function __construct()
    {
    }

    /**
    *
    */
    public function units()
    {
        $tipo = [
          'residenziale',
          'commerciale',
          'uffici',
          'scuole',
          'impianti sportivi'
        ];

        $q = 'SELECT * FROM unita';

        $sth = PostgreSQL::instance()->prepare($q);
        $sth->execute();

        $result = $sth->fetchAll(\PDO::FETCH_ASSOC);
        $retval = [];

        foreach ($result as $unit) {
          $retval[] = [
            'tipo' => $tipo[ $unit['tipo'] ],
            'numero_contatore' => $unit['num_contatore_elettrico'],
            'codice_fiscale' => $unit['cf_intestatario']
          ];
        }

        return [
          'success' => true,
          'payload' => $retval
        ];
    }

    /**
    *
    */
    public function users()
    {
        $q = 'SELECT * FROM dati_utenze_monitorate';

        $sth = PostgreSQL::instance()->prepare($q);
        $sth->execute();

        return $sth->fetchAll(\PDO::FETCH_OBJ);
    }

    /**
    *
    */
    public function authenticate($numero_contatore, $codice_fiscale)
    {
        $q = 'SELECT * FROM dati_utenze_monitorate WHERE numero_contatore = :numero_contatore AND codice_fiscale = :codice_fiscale LIMIT 1';

        try {
          $sth = PostgreSQL::instance()->prepare($q);
          $sth->bindParam(':numero_contatore', $numero_contatore);
          $sth->bindParam(':codice_fiscale', $codice_fiscale);
          $sth->execute();

          $result = $sth->fetch(\PDO::FETCH_ASSOC);

          if (!$result) return [
            'success' => false,
            'message' => 'Numero contatore e/o codice fiscale errati.'
          ];

          return [
            'success' => true,
            'message' => 'Autenticazione effettuata con successo.',
            'payload' => $result
          ];

        } catch (\PDOException $e) {
          return [
            'success' => false,
            'message' => 'errore durante l\'esecuzione della query.'
          ];
        }
    }

    /*
    *
    */
    public function dps($start, $query)
    {
        $url = 'http://' . Config::OPENTSDB_HOST . ':' . Config::OPENTSDB_PORT . "?start={$start}&m={$query}";

        $data = @file_get_contents($url);

        if (false === $data) return ['error' => 'Errore'];

        return $data;
    }
}
