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
    public static function getConsumiGasByNumeroContatoreAndCodiceFiscale($numeroContatore, $codiceFiscale)
    {
        /**
         * TODO: compilare la colonna c.nome_st
         * in modo da restringere i risultati con AND c.nome_st = u.indirizzo
         */
        $q =
            '
            SELECT
                c.anno,
                c.num_mesi_fatturati,
                c.ammontare_fatturato,
                c.consumo_fatturato
            FROM
                gas_rilevamenti c,
                dati_utenze_monitorate u
            WHERE c.cf_titolare LIKE :codice_fiscale
                AND u.numero_contatore = :numero_contatore
            ORDER BY c.anno ASC
            ';

        $codiceFiscale = "%{$codiceFiscale}%";

        $sth = DB::instance()->prepare($q);
        $sth->bindParam(':codice_fiscale', $codiceFiscale);
        $sth->bindParam(':numero_contatore', $numeroContatore, \PDO::PARAM_INT);
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
    public static function getConsumiElettriciByNumeroContatoreAndCodiceFiscale($numeroContatore, $codiceFiscale)
    {
        $q =
            '
            SELECT
                c.anno,
                c.num_mesi_fatturazione,
                c.ammontare_netto_iva,
                c.kw_fatturati
            FROM
                dati_fornitura_rilevamenti c,
                dati_utenze_monitorate u
            WHERE c.cf_titolare LIKE :codice_fiscale
                AND u.numero_contatore = :numero_contatore
                AND c.nome_st = u.indirizzo
                AND c.tipologia = \'E\'
            ORDER BY c.anno ASC
            ';

        $codiceFiscale = "%{$codiceFiscale}%";

        $sth = DB::instance()->prepare($q);
        $sth->bindParam(':codice_fiscale', $codiceFiscale);
        $sth->bindParam(':numero_contatore', $numeroContatore, \PDO::PARAM_INT);
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
                AND s.manutenzione = false
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

        $tipologie = [
            0 => 'privato',
            1 => 'pubblico',
            2 => 'commerciale'
        ];

        $row = self::getUtenzaById($id);

        $data['utenza'] = [
            'id' => $row['id'],
            'codice_fiscale' => $row['codice_fiscale'],
            'tipologia' => $row['tipologia'],
            'tipologia_desc' => self::varVal($row['tipologia'], $tipologie, 'altro')
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
                'parent_id' => $id,
                'denominazione' => self::varVal('denominazione', $row),
                'indirizzo' => self::varVal('indirizzo', $row),
                'civico' => $row['civico'],
                'foglio_catastale' => $row['foglio_catastale'],
                'particella_catastale' => $row['particella_catastale'],
                'anno_costruzione' => (string)$row['anno_costr'],
                'anno_ristrutturazione' => (string)$row['anno_ristr'],
                'utenze_giornaliere' => $row['num_utenti_giorno'],
                'superficie_totale_mq' => self::parseFloat($row['superficie_totale_mq']),
                'volume_totale_mc' => self::parseFloat($row['volume_totale_mc']),
                'numero_piani' => $row['numero_piani'],
                'numero_piani_interrati' => $row['numero_piani_interr'],
                'consumi_annuali' => [
                    'gasolio_lt' => self::parseFloat($row['consumi_gasolio_litri_anno']),
                    'gas_mc' => self::parseFloat($row['consumi_gas_mc_anno']),
                    'olio_lt' => self::parseFloat($row['consumi_olio_litri_anno']),
                    'gpl_lt' => self::parseFloat($row['consumi_gpl_litri_anno'])
                ],
                'tipologia_riscaldamento' => $row['tipologia_riscaldamento'],
                'tipologia_climatizzazione_estiva' => $row['tipologia_climatizzazione_estiva'],
                'potenza_disponibile_w' => self::parseFloat($row['potenza_disponibile']),
                'destinazione_uso' => self::varVal($row['destinazione_uso'], $destinazioneUso, 'altro'),
                'note' => $row['note'],
                'latLon' => self::getLatLon(strtoupper($row['indirizzo']), $row['civico'])
            ];
        }
    }

    /**
     *
     */
    private static function withUnitaImmobiliare($utenza, array &$data)
    {

        $tipologia = [
            0 => 'residenziale',
            1 => 'commerciale',
            2 => 'uffici',
            3 => 'scuole',
            4 => 'impianti sportivi'
        ];

        foreach (self::getUnitaUmmobiliariByIdUtenza($utenza['id']) as $row) {

            $consumi = [];
            self::withConsumi($utenza, $row['num_contatore_elettrico'], $consumi);

            $data['unita_immobiliari'][] = [
                'id' => (string)$row['num_contatore_elettrico'],
                'parent' => [
                    'id_edificio' => $row['id_edificio']
                ],
                'parent_id' => (string)$row['id_edificio'],
                'tipologia' => self::varVal($row['tipo'], $tipologia),
                'consumi_annuali' => [
                    'elettrici_kwh' => self::parseFloat($row['consumi_elettrici_kwh_anno']),
                    'idrici_mc' => self::parseFloat($row['consumi_idrici_mc_anno']),
                    'gas_mc' => self::parseFloat($row['consumi_gas_mc_anno'])
                ],
                'subalterno' => $row['subalterno'],
                'potenza_disponibile_w' => self::parseFloat($row['potenza_disponibile']),
                'note' => $row['note'],
                'consumi' => $consumi
            ];
        }
    }

    /**
     *
     */
    private static function varVal($k, array $arr, $default = 'n/d')
    {
        return isset($arr[$k]) ? $arr[$k] : $default;
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
                'parent_id' => (string)$row['id_unita'],
                'tipologia' => self::varVal('tipo', $row),
                'superficie_mq' => self::parseFloat($row['superficie_mq']),
                'infissi' => [
                    'tipologia_vetro' => self::varVal($row['infissi_tipologia_vetro'], $tipologiaVetroInfissi),
                    'tipologia_telaio' => self::varVal($row['infissi_tipologia_telaio'], $tipologiaTelaioInfissi)
                ]
            ];
        }
    }

    /**
     *
     */
    private static function withIlluminazione($id, &$data)
    {
        $tipologia = [
            0 => 'LED',
            1 => 'Fluorescente',
            2 => 'A scarica'
        ];

        foreach (self::getIlluminazioneByIdUtente($id) as $row) {
            $data['illuminazione'][] = [
                'id' => $row['id'],
                'parent' => [
                    'id_zona' => $row['id_zona']
                ],
                'parent_id' => $row['id_zona'],
                'tipologia' => self::varVal($row['tipologia'], $tipologia),
                'quantita' => $row['quantita'],
                'potenza_nominale_w' => self::parseFloat($row['potenza_nominale'])
            ];
        }
    }

    /**
     *
     */
    private static function parseFloat($str) {
        if (strstr($str, ',')) {
            $str = str_replace('.', '', $str);
            $str = str_replace(',', '.', $str);
        }

        return floatval($str);
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
            13 => 'forno a microonde'
        ];

        foreach (self::getDispositiviElettriciByIdUtenza($id) as $row) {
            $data['dispositivi_elettrici'][] = [
                'id' => $row['id'],
                'parent' => [
                    'id_unita_immobiliare' => (string)$row['id_unita']
                ],
                'parent_id' => (string)$row['id_unita'],
                'tipologia' => self::varVal($row['tipo'], $tipologia, 'altro'),
                'conteggio' => $row['numero'],
                'potenza_nominale_w' => self::parseFloat($row['potenza_nominale']),
                'modalita_utilizzo_h_g' => self::parseFloat($row['modalita_utilizzo_h_g'])
            ];
        }
    }

    /**
     *
     */
    private static function withSensori($id, array &$data)
    {

        $tipologia = [
            0 => [
                'energia_elettrica',
                'Misuratore contatore elettrico'
            ],
            1 => [
                'generico',
                'Misuratore ad impulsi generico'
            ],
            2 => [
                'ambientale',
                'Misuratore ambientale'
            ],
            3 => [
                'ambientale_out_2ch',
                'Misuratore ambientale (2ch)'
            ],
            4 => [
                'ambientale_out_3ch',
                'Misuratore ambientale (3ch)'
            ],
            5 => [
                'ambientale_out_meteo_3ch',
                'Meteo'
            ],
            6 => [
                'produzione',
                'Fotovoltaico'
            ]
        ];

        foreach (self::getSensoriByIdUtenza($id) as $row) {

            /*
             * Se $tipologia non ha un indice per $row['tipologia'],
             * allora vuol dire che è stato utilizzato un nuovo sensore e per
             * evitare errori di richiesta per OpenTSDB, allora non conteggia
             * il nuovo sensore.
             */
            if (!self::varVal($row['tipologia'], $tipologia, false)) continue;

            $data['sensori'][] = [
                'parent' => [
                    'id_unita_immobiliare' => (string)$row['numero_contatore']
                ],
                'parent_id' => (string)$row['numero_contatore'],
                'mac_address' => $row['mac'],
                'mac_address_datalogger' => $row['mac_datalogger'],
                'numero_canali' => $row['numero_canali'],
                'tipologia' => $tipologia[$row['tipologia']][0],
                'tipologia_desc' => $tipologia[$row['tipologia']][1],
                'in_manutenzione' => $row['manutenzione'],
                'ultimo_aggiornamento' => $row['lastupdate']
            ];
        }
    }

    /**
     *
     */
    private static function withConsumi($utenza, $numeroContatore, array &$data)
    {
        foreach (self::getConsumiElettriciByNumeroContatoreAndCodiceFiscale($numeroContatore, $utenza['codice_fiscale']) as $row) {
            $data['elettrici'][] = [
                'anno' => (string)$row['anno'],
                'numero_mesi_fatturati' => $row['num_mesi_fatturazione'],
                'ammontare_netto_iva' => self::parseFloat($row['ammontare_netto_iva']),
                'kw_fatturati' => self::parseFloat($row['kw_fatturati'])
            ];
        }

        foreach (self::getConsumiGasByNumeroContatoreAndCodiceFiscale($numeroContatore, $utenza['codice_fiscale']) as $row) {
            $data['gas'][] = [
                'anno' => (string)$row['anno'],
                'numero_mesi_fatturati' => $row['num_mesi_fatturati'],
                'ammontare_netto_iva' => self::parseFloat($row['ammontare_fatturato']),
                'mc_fatturati' => self::parseFloat($row['consumo_fatturato'])
            ];
        }
    }

    /**
     *
     */
    public static function getGeoJson($featureType)
    {
        $q =
            '
            SELECT ST_AsGeoJSON(ST_Transform(geom, 4326)) json
            FROM %s
            ';

        // Sanitizzo il nome della tabella
        $q = sprintf($q, preg_replace('|[^a-z_]*|i', '', $featureType));

        $sth = DB::instance()->prepare($q);
        $sth->execute();

        $result = [];
        while ($row = $sth->fetch()) {
            $result[] = json_decode($row['json']);
        }

        return $result;
    }

    /**
     *
     */
    private static function withGeoJson($featureType, array &$data)
    {
        if (!isset($data['geo'])) $data['geo'] = [];

        foreach (self::getGeoJson($featureType) as $i => $row) {
            $data['geo'][$featureType][] = [
                'type' => 'Feature',
                'geometry' => $row,
                'id' => "{$featureType}.{$i}"
            ];
        }
    }

    /**
     *
     */
    private static function getLatLon($indirizzo, $civico)
    {
        $q =
            '
            SELECT ST_AsGeoJSON(ST_Transform(geom, 4326)) json
            FROM civici c
            WHERE indir LIKE :indirizzo
                AND civ = :civico LIMIT 1
            ';

        $indirizzo = "%{$indirizzo}%";

        $sth = DB::instance()->prepare($q);
        $sth->bindParam(':indirizzo', $indirizzo);
        $sth->bindParam(':civico', $civico, \PDO::PARAM_INT);
        $sth->execute();

        /** */
        $latLon = [0,0];

        $row = $sth->fetch();

        if (isset($row['json'])) {
            $row = json_decode($row['json']);

            if (isset($row->coordinates[0])) {
                $row = $row->coordinates[0];

                $latLon = [$row[1], $row[0]];
            }
        }

        return $latLon;
    }

    /**
     *
     */
    public static function getProfilo($id, $tipologia, $incsQuery = '')
    {

        $incs = [];
        $data = [];

        if ($incsQuery) $incs = explode(',', $incsQuery);

        /** */
        self::withUtenza($id, $data);

        /** */
        if (in_array('e', $incs))
            self::withEdifici($id, $data);

        if (in_array('ui', $incs))
            self::withUnitaImmobiliare($data['utenza'], $data);

        if (in_array('z', $incs))
            self::withZone($id, $data);

        if (in_array('i', $incs))
            self::withIlluminazione($id, $data);

        if (in_array('de', $incs))
            self::withDispositiviElettrici($id, $data);

        if (in_array('s', $incs))
            self::withSensori($id, $data);

        /** */
        if (in_array('g', $incs)) {
            self::withGeoJson('perimetro_quartiere', $data);
            self::withGeoJson('grafo_viario', $data);

            if ($tipologia == 0)
                self::withGeoJson('privato', $data);
            else if ($tipologia == 1)
                self::withGeoJson('pubblico', $data);
            else if ($tipologia == 2)
                self::withGeoJson('commerciale', $data);
        }


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
     private static function getMeteoNomeIcona($icona)
     {
         $condizioni = [
             '01' => 'day-sunny', //'clear sky',
             '02' => 'cloudy', //'few clouds',
             '03' => 'cloudy', //'scattered clouds',
             '04' => 'cloud', //'broken clouds',
             '09' => 'shower', //'shower rain',
             '10' => 'rain',
             '11' => 'thunderstorm',
             '13' => 'snow',
             '50' => 'dust', //'mist'
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
             'data' => date('j M Y H:m', $result['dt']),
             'icona' => self::getMeteoNomeIcona($result['weather'][0]['icon']),
             'temperatura' => ceil($result['main']['temp']),
             'umidita_pct' => $result['main']['humidity'],
             'nuvole_pct' => $result['clouds']['all'],
             'vento' => [
                 'velocita_km_h' => ceil($result['wind']['speed'] * 3.6), // converto i m/s in Km/h
                 //'direzione_deg' => ceil($result['wind']['deg'])
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
                 'data' => date('j M Y', $row['dt']),
                 'icona' => self::getMeteoNomeIcona($row['weather'][0]['icon']),
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
