<?php

namespace sideco;

use \sideco\DB;

class Store {

    /**
     *
     */
    public static function catalog($table = null)
    {
        if (is_null($table)) {

            $q =
                "
                SELECT *
                FROM pg_catalog.pg_tables
                WHERE schemaname = 'public'
                ";

            $sth = DB::instance()->query($q);

        } else {

            // Sanitizzo il nome della tabella
            $table = preg_replace('|[^a-z_]*|i', '', $table);

            $q = "SELECT * FROM {$table} LIMIT 10";

            try {
                $sth = DB::instance()->prepare($q);
                $sth->execute();
            } catch (\PDOException $e) {
                return false;
            }
        }

        $raw = $sth->fetchAll();

        if (!is_null($table)) return $raw;

        $data = [];
        foreach ($raw as $table) {
            $data[] = $table['tablename'];
        }

        return $data;
    }

    /**
     *
     */
    public static function getUtenzaById($id)
    {
        $q =
            '
            SELECT u.*
            FROM utenze u
            WHERE u.id = :id
            LIMIT 1
            ';

        $sth = DB::instance()->prepare($q);
        $sth->bindParam(':id', $id, \PDO::PARAM_INT);
        $sth->execute();

        return $sth->fetch() ?: false;
    }

    /**
     *
     */
    public static function getNumeroContatoriByIdUtenza($id)
    {
        $q =
            '
            SELECT DISTINCT ON (ui.num_contatore_elettrico)
                ui.num_contatore_elettrico
            FROM utenze u, unita ui
            WHERE u.id = :id_utenza
                AND u.codice_fiscale = ui.cf_intestatario
            ';

        $sth = DB::instance()->prepare($q);
            $sth->bindParam(':id_utenza', $id, \PDO::PARAM_INT);
        $sth->execute();

        $result = [];

        while ($row = $sth->fetch()) {
            $result[] = (string)$row['num_contatore_elettrico'];
        }

        return $result;
    }

    /**
     *
     */
    public static function getEdificiByIdUtenza($id)
    {
        $q =
            '
            SELECT e.*
            FROM edificio e
            WHERE e.id_edificio IN (
                SELECT ui.id_edificio
                FROM
                    unita ui,
                    utenze u
                WHERE u.id = :id
                    AND ui.cf_intestatario = u.codice_fiscale
            )
            ';

        $sth = DB::instance()->prepare($q);
        $sth->bindParam(':id', $id, \PDO::PARAM_INT);
        $sth->execute();

        $result = [];
        while ($row = $sth->fetch()) {
            $result[] = $row;
        }

        return $result;
    }

    /**
     *
     */
    public static function getUnitaUmmobiliariByIdUtenza($id)
    {
        $q =
            '
            SELECT ui.*
            FROM
                unita ui,
                utenze u
            WHERE u.id = :id
                AND ui.cf_intestatario = u.codice_fiscale
            ';

        $sth = DB::instance()->prepare($q);
        $sth->bindParam(':id', $id, \PDO::PARAM_INT);
        $sth->execute();

        $result = [];
        while ($row = $sth->fetch()) {
            $result[] = $row;
        }

        return $result;
    }

    /**
     *
     */
    public static function getDispositiviElettriciByIdUtenza($id)
    {
        $q =
            '
            SELECT d.*
            FROM dispositivo_elettrico d
            WHERE d.id_unita IN (
                SELECT ui.num_contatore_elettrico
                FROM
                    unita ui,
                    utenze u
                WHERE u.id = :id
                    AND ui.cf_intestatario = u.codice_fiscale
            )
            ';

        $sth = DB::instance()->prepare($q);
        $sth->bindParam(':id', $id, \PDO::PARAM_INT);
        $sth->execute();

        $result = [];
        while ($row = $sth->fetch()) {
            $result[] = $row;
        }

        return $result;
    }

    /**
     *
     */
    public static function getConsumiElettriciByIdUtenza($id)
    {
        $q =
            '
            SELECT c.*
            FROM
                dati_fornitura_rilevamenti c,
                utenze u
            WHERE u.id = :id
                AND c.cf_titolare = u.codice_fiscale
                AND c.tipologia = \'E\'
            ';

        $sth = DB::instance()->prepare($q);
        $sth->bindParam(':id', $id, \PDO::PARAM_INT);
        $sth->execute();

        $result = [];
        while ($row = $sth->fetch()) {
            $result[] = $row;
        }

        return $result;
    }

    /**
     *
     */
    public static function getConsumiGasByIdUtenza($id)
    {
        $q =
            '
            SELECT c.*
            FROM
                gas_rilevamenti c,
                utenze u
            WHERE u.id = :id
                AND c.cf_titolare = u.codice_fiscale
            ';

        $sth = DB::instance()->prepare($q);
        $sth->bindParam(':id', $id, \PDO::PARAM_INT);
        $sth->execute();

        $result = [];
        while ($row = $sth->fetch()) {
            $result[] = $row;
        }

        return $result;
    }

    /**
     *
     */
    public static function getSensoriByIdUtenza($id)
    {
        $q =
            '
            SELECT s.*
            FROM
                cap_smartmeter s,
                utenze u,
                dati_utenze_monitorate um
            WHERE u.id = :id
                AND u.codice_fiscale = um.codice_fiscale
                AND um.numero_contatore = s.numero_contatore
            ';

        $sth = DB::instance()->prepare($q);
        $sth->bindParam(':id', $id, \PDO::PARAM_INT);
        $sth->execute();

        $result = [];
        while ($row = $sth->fetch()) {
            $result[] = $row;
        }

        return $result;
    }

    /**
     *
     */
    public static function getZoneByIdUtente($id)
    {
        $q =
            '
            SELECT z.*
            FROM zona z
            WHERE z.id_unita IN (
                SELECT ui.num_contatore_elettrico
                FROM
                    unita ui,
                    utenze u
                WHERE u.id = :id
                    AND ui.cf_intestatario = u.codice_fiscale
            )
            ';

        $sth = DB::instance()->prepare($q);
        $sth->bindParam(':id', $id, \PDO::PARAM_INT);
        $sth->execute();

        $result = [];
        while ($row = $sth->fetch()) {
            $result[] = $row;
        }

        return $result;
    }

    /**
     *
     */
    public static function getIlluminazioneByIdUtente($id)
    {
        $q =
            '
            SELECT i.*
            FROM illuminazione i
            WHERE i.id_zona IN (
                SELECT z.id
                FROM
                    zona z,
                    utenze u,
                    unita ui
                WHERE u.id = :id
                    AND u.codice_fiscale = ui.cf_intestatario
                    AND ui.num_contatore_elettrico = z.id_unita
            )
            ';

        $sth = DB::instance()->prepare($q);
        $sth->bindParam(':id', $id, \PDO::PARAM_INT);
        $sth->execute();

        $result = [];
        while ($row = $sth->fetch()) {
            $result[] = $row;
        }

        return $result;
    }

    /**
     *
     */
    private static function withUtenza($id, array &$data)
    {

        $row = self::getUtenzaById($id);

        $data['utenza'] = [
            'id' => $row['id'],
            'codice_fiscale' => $row['codice_fiscale'],
            'tipologia' => $row['tipologia']
        ];
    }

    /**
     *
     */
    private static function withEdifici($id, array &$data)
    {

        $destinazioneUso = [
            0 => 'civile abitazione',
            1 => 'commerciale',
            2 => 'p.a. uffici',
            3 => 'p.a. scuola',
            4 => 'p.a. altro',
            5 => 'misto'
        ];

        foreach (self::getEdificiByIdUtenza($id) as $row) {
            $data['edifici'][] = [
                'id' => $row['id_edificio'],
                'parent' => [
                    'id_utenza' => $id
                ],
                'denominazione' => $row['denominazione'],
                'indirizzo' => $row['indirizzo'],
                'civico' => $row['civico'],
                'foglio_catastale' => $row['foglio_catastale'],
                'particella_catastale' => $row['particella_catastale'],
                'anno_costruzione' => $row['anno_costr'],
                'anno_ristrutturazione' => $row['anno_ristr'],
                'utenze_giornaliere' => $row['num_utenti_giorno'],
                'superficie_totale_mq' => $row['superficie_totale_mq'],
                'volume_totale_mc' => $row['volume_totale_mc'],
                'numero_piani' => $row['numero_piani'],
                'numero_piani_interrati' => $row['numero_piani_interr'],
                'consumi_annuali' => [
                    'gasolio_lt' => $row['consumi_gasolio_litri_anno'],
                    'gas_mc' => $row['consumi_gas_mc_anno'],
                    'olio_lt' => $row['consumi_olio_litri_anno'],
                    'gpl_lt' => $row['consumi_gpl_litri_anno']
                ],
                'tipologia_riscaldamento' => $row['tipologia_riscaldamento'],
                'tipologia_climatizzazione_estiva' => $row['tipologia_climatizzazione_estiva'],
                'potenza_disponibile_w' => $row['potenza_disponibile'],
                'destinazione_uso' => $destinazioneUso[$row['destinazione_uso']],
                'note' => $row['note']
            ];
        }
    }

    /**
     *
     */
    private static function withUnitaImmobiliare($id, array &$data)
    {

        $tipologia = [
            0 => 'residenziale',
            1 => 'commerciale',
            2 => 'uffici',
            3 => 'scuole',
            4 => 'impianti sportivi'
        ];

        foreach (self::getUnitaUmmobiliariByIdUtenza($id) as $row) {
            $data['unita_immobiliari'][] = [
                'id' => (string)$row['num_contatore_elettrico'],
                'parent' => [
                    'id_edificio' => $row['id_edificio']
                ],
                'tipologia' => $tipologia[$row['tipo']],
                'consumi_annuali' => [
                    'elettrici_kwh' => $row['consumi_elettrici_kwh_anno'],
                    'idrici_mc' => $row['consumi_idrici_mc_anno'],
                    'gas_mc' => $row['consumi_gas_mc_anno']
                ],
                'subalterno' => $row['subalterno'],
                'potenza_disponibile_w' => $row['potenza_disponibile'],
                'note' => $row['note']
            ];
        }
    }

    /**
     *
     */
    private static function withZone($id, array &$data)
    {

        $tipologiaVetroInfissi = [
            0 => 'vetro singolo',
            1 => 'doppio vetro normale',
            2 => 'triplo vetro normale',
            3 => 'vetrocamera',
            4 => 'doppio vetro con rivestimento basso emissivo',
            5 => 'triplo vetro con rivestimento basso emissivo'
        ];

        $tipologiaTelaioInfissi = [
            0 => 'telaio metallico',
            1 => 'telaio in legno/pvc',
            2 => 'telaio in alluminio senza taglio termico',
            3 => 'telaio in alluminio con taglio termico'
        ];

        foreach (self::getZoneByIdUtente($id) as $row) {
            $data['zone'][] = [
                'id' => $row['id'],
                'parent' => [
                    'id_unita_immobiliare' => (string)$row['id_unita']
                ],
                'tipologia' => $row['tipo'],
                'superficie_mq' => $row['superficie_mq'],
                'infissi' => [
                    'tipologia_vetro' => $tipologiaVetroInfissi[$row['infissi_tipologia_vetro']],
                    'tipologia_telaio' => $tipologiaTelaioInfissi[$row['infissi_tipologia_telaio']]
                ]
            ];
        }
    }

    /**
     *
     */
    private static function withIlluminazione($id, &$data)
    {
        foreach (self::getIlluminazioneByIdUtente($id) as $row) {
            $data['illuminazione'][$row['id_zona']][] = $row;
        }
    }

    /**
     *
     */
    private static function withDispositiviElettrici($id, array &$data)
    {

        $tipologia = [
             0 => 'congelatore verticale',
             1 => 'forno elettrico',
             2 => 'ferro da stiro',
             3 => 'lavastoviglie',
             4 => 'lavatrice',
             5 => 'phon',
             6 => 'piastra per capelli',
             7 => 'televisore',
             8 => 'frigorifero',
             9 => 'climatizzatore',
            10 => 'personal computer',
            11 => 'stampante',
            12 => 'tostapane',
            13 => 'forno a microonde',
            14 => 'altro'
        ];

        foreach (self::getDispositiviElettriciByIdUtenza($id) as $row) {
            $data['dispositivi_elettrici'][] = [
                'id' => $row['id'],
                'parent' => [
                    'id_unita_immobiliare' => $row['id_unita']
                ],
                'tipologia' => $tipologia[$row['tipo']],
                'conteggio' => $row['numero'],
                'potenza_nominale_w' => $row['potenza_nominale'],
                'modalita_utilizzo_h_g' => $row['modalita_utilizzo_h_g']
            ];
        }
    }

    /**
     *
     */
    private static function withSensori($id, array &$data)
    {

        $tipologia = [
            0 => 'energia_elettrica',
            1 => 'misuratore ad impulsi generico',
            2 => 'ambientale'
        ];

        foreach (self::getSensoriByIdUtenza($id) as $row) {
            $data['sensori'][] = [
                'parent' => [
                    'id_unita_immobiliare' => (string)$row['numero_contatore']
                ],
                'mac_address' => $row['mac'],
                'mac_address_datalogger' => $row['mac_datalogger'],
                'numero_canali' => $row['numero_canali'],
                'tipologia' => $tipologia[$row['tipologia']],
                'in_manutenzione' => $row['manutenzione'],
                'ultimo_aggiornamento' => $row['lastupdate']
            ];
        }
    }

    /**
     *
     */
    private static function withConsumi($id, array &$data)
    {

        foreach (self::getConsumiElettriciByIdUtenza($id) as $row) {
            $data['consumi']['elettrici'][] = [
                'anno' => $row['anno'],
                'numero_mesi_fatturati' => $row['num_mesi_fatturazione'],
                'ammontare_netto_iva' => $row['ammontare_netto_iva'],
                'kw_fatturati' => $row['kw_fatturati']
            ];
        }

        foreach (self::getConsumiGasByIdUtenza($id) as $row) {
            $data['consumi']['gas'][] = [
                'anno' => $row['anno'],
                'numero_mesi_fatturati' => $row['num_mesi_fatturati'],
                'ammontare_netto_iva' => $row['ammontare_fatturato'],
                'mc_fatturati' => $row['consumo_fatturato']
            ];
        }
    }


    /**
     *
     */
    public static function getProfilo($id, $incsQuery = '')
    {

        $incs = [];
        $data = [];

        if ($incsQuery) $incs = explode(',', $incsQuery);

        if (in_array('u', $incs))
            self::withUtenza($id, $data);

        if (in_array('e', $incs))
            self::withEdifici($id, $data);

        if (in_array('ui', $incs))
            self::withUnitaImmobiliare($id, $data);

        if (in_array('z', $incs))
            self::withZone($id, $data);

        if (in_array('i', $incs))
            self::withIlluminazione($id, $data);

        if (in_array('de', $incs))
            self::withDispositiviElettrici($id, $data);

        if (in_array('s', $incs))
            self::withSensori($id, $data);

        if (in_array('c', $incs))
            self::withConsumi($id, $data);

        return $data;
    }

    /**
     *
     */
     private static function prepareOpenTSDBQueryString(array $args = array())
     {

         $defaults = [
             'start' => '1h-ago',
             'end' => time(),
             'aggregator' => 'sum',
             'downsample' => '',
             'metric' => '',
             'tags' => []
         ];

         $args = array_merge($defaults, $args);

         $tags = [];
         foreach ($args['tags'] as $k => $v) {
             $tags[] = "{$k}={$v}";
         }
         $tags = join(',', $tags);

         $tags = $tags ? "{{$tags}}" : '';

         $aggregator = $args['aggregator'] ? "{$args['aggregator']}:" : '';
         $metric = $args['metric'];
         $downsample = $args['downsample'] ? "{$args['downsample']}:" : '';

         $url = "?start={$args['start']}&end={$args['end']}&m={$aggregator}{$downsample}{$metric}{$tags}";

         return $url;
     }

    /**
     *
     */
     public static function getSensoreDataByNumeroContatore($numeroContatore, $metrica, $canale = 1, array $queryParams = array())
     {
         $defaultParams = [
             'start' => '1h-ago',
             'end' => time(),
             'aggregator' => 'sum',
             'downsample' => ''
         ];
         $queryParams = array_merge($defaultParams, $queryParams);

         $q = self::prepareOpenTSDBQueryString(
             array_merge($queryParams, [
                 'metric' => $metrica,
                 'tags' => [
                     'utenza' => $numeroContatore,
                     'canale' => $canale
                 ]
             ])
         );

         $url = 'http://' . Config::SIDECO_HOST . ':' . Config::OPENTSDB_PORT . "/api/query/{$q}";

         $data = @file_get_contents($url);

         return $data ? json_decode($data) : false;
     }

     /**
      *
      */
     private static function getMeteoNomeCondizione($icona)
     {
         $condizioni = [
             '01' => 'clear sky',
             '02' => 'few clouds',
             '03' => 'scattered clouds',
             '04' => 'broken clouds',
             '09' => 'shower rain',
             '10' => 'rain',
             '11' => 'thunderstorm',
             '13' => 'snow',
             '50' => 'mist'
         ];

         return $condizioni[preg_replace('|[^\d]+|', '', $icona)];
     }

     /**
      *
      */
     private static function withWeather(&$data)
     {
         $url = 'http://api.openweathermap.org/data/2.5/weather?id=6541869&units=metric&APPID=' . Config::OWM_APPID;

         $result = @file_get_contents($url);

         if (!$result) return false;

         $result = json_decode($result, true);

         $data['attuale'] = [
             'data' => date('j/n/Y H:i', $result['dt']),
             'condizione' => self::getMeteoNomeCondizione($result['weather'][0]['icon']),
             'temperatura' => ceil($result['main']['temp']),
             'umidita_pct' => $result['main']['humidity'],
             'nuvole_pct' => $result['clouds']['all'],
             'vento' => [
                 'velocita_km_h' => ceil($result['wind']['speed'] * 3.6), // converto i m/s in Km/h
                 'direzione_deg' => ceil($result['wind']['deg'])
             ]
         ];
     }

     /**
      *
      */
     private static function withForecast(&$data)
     {
         $url = 'http://api.openweathermap.org/data/2.5/forecast/daily?id=6541869&cnt=16&units=metric&APPID=' . Config::OWM_APPID;

         $result = @file_get_contents($url);

         if (!$result) return false;

         $result = json_decode($result, true);

         foreach ($result['list'] as $row) {
             $data['previsioni'][] = [
                 'data' => date('j/n/Y H:i', $row['dt']),
                 'condizione' => self::getMeteoNomeCondizione($row['weather'][0]['icon']),
                 'temperature' => [
                     'min' => ceil($row['temp']['min']),
                     'max' => ceil($row['temp']['max'])
                 ],
                 'umidita_pct' => $row['humidity'],
                 'nuvole_pct' => $row['clouds'],
                 'vento' => [
                     'velocita_km_h' => ceil($row['speed'] * 3.6),
                     'direzione_deg' => ceil($row['deg'])
                 ]
             ];
         }
     }

     /**
      *
      */
     public static function getMeteo($incsQuery = '')
     {
         $incs = [];
         $data = [];

         if ($incsQuery) $incs = explode(',', $incsQuery);

         if (in_array('w', $incs))
            self::withWeather($data);

         if (in_array('f', $incs))
            self::withForecast($data);

         return $data;
     }
}
