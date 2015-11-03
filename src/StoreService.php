<?php
namespace Sideco;

use \Sideco\PostgreSQL as DB;
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
    public function catalog($table = null)
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
            $data[] = $table->tablename;
        }

        return $data;
    }

    /**
     *
     */
    public function getUtenza($id_utenza)
    {
        $q =
            '
            SELECT
                u.id id_utenza,
                u.codice_fiscale,
                u.tipologia
            FROM utenze u
            WHERE u.id = :id_utenza
            LIMIT 1
            ';

        $sth = DB::instance()->prepare($q);
            $sth->bindParam(':id_utenza', $id_utenza, \PDO::PARAM_INT);
        $sth->execute();

        return $sth->fetch();
    }

    /**
     *
     */
    public function getUnitaImmobiliariByUtenza($id_utenza)
    {
        $q =
            '
            SELECT
                uu.id_edificio,
                uu.num_contatore_elettrico numero_contatore,
                uu.tipo tipologia,
                uu.cf_intestatario codice_fiscale,
                uu.consumi_elettrici_kwh_anno,
                uu.consumi_idrici_mc_anno,
                uu.consumi_gas_mc_anno,
                uu.subalterno,
                uu.potenza_disponibile,
                uu.note
            FROM utenze u, unita uu
            WHERE u.id = :id_utenza
                AND u.codice_fiscale = uu.cf_intestatario
            ';

        $sth = DB::instance()->prepare($q);
            $sth->bindParam(':id_utenza', $id_utenza, \PDO::PARAM_INT);
        $sth->execute();

        $tipologia = [
            0 => 'residenziale',
            1 => 'commerciale',
            2 => 'uffici',
            3 => 'scuole',
            4 => 'impianti sportivi'
        ];

        $retval = [];

        $results = $sth->fetchAll();

        // Normalizzo
        foreach ($results as $result) {
            $ui = array();
            $ui['id_edificio'] = $result->id_edificio;
            $ui['numero_contatore'] = $result->numero_contatore;
            $ui['tipologia'] = $tipologia[$result->tipologia];
            $ui['codice_fiscale'] = $result->codice_fiscale;
            $ui['consumi_annuali'] = [
                'elettrici_kwh' => $result->consumi_elettrici_kwh_anno,
                'idrici_mc' => $result->consumi_idrici_mc_anno,
                'gas_mc' => $result->consumi_gas_mc_anno
            ];
            $ui['subalterno'] = $result->subalterno;
            $ui['potenza_disponibile'] = $result->potenza_disponibile;
            $ui['note'] = $result->note;

            $retval[$result->numero_contatore] = $ui;
        }

        return $retval;
    }

    /**
     *
     */
    public function getNumeroContatoriByUtenza($id_utenza) {
        $q =
            '
            SELECT DISTINCT ON (ui.num_contatore_elettrico)
                ui.num_contatore_elettrico numero_contatore
            FROM utenze u, unita ui
            WHERE u.id = :id_utenza
                AND u.codice_fiscale = ui.cf_intestatario
            ';

        $sth = DB::instance()->prepare($q);
            $sth->bindParam(':id_utenza', $id_utenza, \PDO::PARAM_INT);
        $sth->execute();

        $result = [];

        while ($row = $sth->fetch()) {
            $result[] = (string)$row->numero_contatore;
        }

        return $result;
    }

    /**
     *
     */
    public function getEdificio($id_edificio)
    {
        $q =
            '
            SELECT *
            FROM edificio e
            WHERE e.id_edificio = :id_edificio
            LIMIT 1
            ';

        $sth = DB::instance()->prepare($q);
            $sth->bindParam(':id_edificio', $id_edificio, \PDO::PARAM_INT);
        $sth->execute();

        $result = $sth->fetch();
        $retval = [];

        if (!$result) return $retval;

        $destinazioneUso = [
            0 => 'civile abitazione',
            1 => 'commerciale',
            2 => 'p.a. uffici',
            3 => 'p.a. scuola',
            4 => 'p.a. altro',
            5 => 'misto'
        ];

        // Normalizzo
        $retval['id_edificio'] = $result->id_edificio;
        $retval['destinazione_uso'] = $destinazioneUso[$result->destinazione_uso];
        $retval['denominazione'] = $result->denominazione;
        $retval['identificativi_catastali'] = [
            'foglio' => $result->foglio_catastale,
            'particella' => $result->particella_catastale
        ];
        $retval['anno_costruzione'] = $result->anno_costr;
        $retval['anno_ristrutturazione'] = $result->anno_ristr;
        $retval['utenze_giornaliere'] = $result->num_utenti_giorno;
        $retval['superficie_totale_mq'] = $result->superficie_totale_mq;
        $retval['volume_totale_mc'] = $result->volume_totale_mc;
        $retval['numero_piani'] = $result->numero_piani;
        $retval['numero_piani_interrati'] = $result->numero_piani_interr;
        $retval['consumi_annuali'] = [
            'gasolio_lt' => $result->consumi_gasolio_litri_anno,
            'gas_mc' => $result->consumi_gas_mc_anno,
            'olio_lt' => $result->consumi_olio_litri_anno,
            'gpl_lt' => $result->consumi_gpl_litri_anno
        ];
        $retval['tipologia_riscaldamento'] = $result->tipologia_riscaldamento;
        $retval['tipologia_climatizzazione_estiva'] = $result->tipologia_climatizzazione_estiva;
        $retval['potenza_disponibile_w'] = $result->potenza_disponibile;

        return $retval;
    }

    /**
     *
     */
    public function getDatiFornituraElettrica($codiceFiscale) {
        $q =
            '
            SELECT
                b.anno,
                b.num_mesi_fatturazione numero_mesi_fatturazione,
                b.ammontare_netto_iva,
                b.kw_fatturati
            FROM dati_fornitura a, dati_fornitura_rilevamenti b
            WHERE b.fk_dati_fornitura = a.id_dati_fornitura
                AND b.cf_titolare = :codice_fiscale
                AND b.tipologia = \'E\'
            ORDER BY b.anno ASC
            ';

        $sth = DB::instance()->prepare($q);
            $sth->bindParam(':codice_fiscale', $codiceFiscale, \PDO::PARAM_STR);
        $sth->execute();

        return $sth->fetchAll();
    }

    /**
     *
     */
    public function getDatiFornituraIdrica($codiceFiscale) {
        return [];
    }

    /**
     *
     */
    public function getDatiFornituraGas($codiceFiscale) {
        $q =
            '
            SELECT
                a.anno,
                a.num_mesi_fatturati numero_mesi_fatturazione,
                a.ammontare_fatturato ammontare_netto_iva,
                a.consumo_fatturato mc_fatturati
            FROM gas_rilevamenti a
            WHERE a.cf_titolare = :codice_fiscale
            ORDER BY a.anno ASC
            ';

        $sth = DB::instance()->prepare($q);
            $sth->bindParam(':codice_fiscale', $codiceFiscale, \PDO::PARAM_STR);
        $sth->execute();

        return $sth->fetchAll();
    }

    /**
     *
     */
    public function getSensoriByUtenza($id_utenza) {
        $q =
            '
            SELECT *
            FROM cap_smartmeter s
            WHERE s.numero_contatore IN(
                SELECT DISTINCT ON (ui.num_contatore_elettrico)
                    ui.num_contatore_elettrico numero_contatore
                FROM utenze u, unita ui
                WHERE u.id = :id_utenza
                    AND u.codice_fiscale = ui.cf_intestatario
            )
            ';

        $sth = DB::instance()->prepare($q);
            $sth->bindParam(':id_utenza', $id_utenza, \PDO::PARAM_INT);
        $sth->execute();

        $tipologia = [
            0 => 'energia_elettrica',
            1 => 'misuratore ad impulsi generico',
            2 => 'ambientale'
        ];

        $results = [];
        while ($row = $sth->fetch()) {
            $result['mac_address'] = $row->mac;
            $result['numero_canali'] = $row->numero_canali;
            //$result['numero_contatore'] = $row->numero_contatore;
            $result['tipologia'] = $tipologia[$row->tipologia];
            $result['in_manutenzione'] = $row->manutenzione;
            $result['ultimo_aggiornamento'] = $row->lastupdate;

            $results[$row->numero_contatore][] = $result;
        }

        return $results;
    }

    /**
     *
     */
    public function getZonaByNumeroContatore($numeroContatore) {
        $q =
            '
            SELECT *
            FROM zona z
            WHERE z.id_unita = :numero_contatore
            LIMIT 1
            ';

        $sth = DB::instance()->prepare($q);
            $sth->bindParam(':numero_contatore', $numeroContatore, \PDO::PARAM_INT);
        $sth->execute();

        return $sth->fetchAll();
    }

    /**
     *
     */
    public function getIlluminazioneByZona($idZona) {
        $q =
            '
            SELECT *
            FROM illuminazione i
            WHERE i.id_zona = :id_zona
            ';

        $sth = DB::instance()->prepare($q);
            $sth->bindParam(':id_zona', $idZona, \PDO::PARAM_INT);
        $sth->execute();

        return $sth->fetchAll();
    }

    /**
     *
     */
    private function populateStoricoConsumi($codiceFiscale) {
        $storicoConsumi['elettrici'] = $this->getDatiFornituraElettrica($codiceFiscale);
        $storicoConsumi['idrici'] = $this->getDatiFornituraIdrica($codiceFiscale);
        $storicoConsumi['gas'] = $this->getDatiFornituraGas($codiceFiscale);

        return $storicoConsumi;
    }

    /**
     *
     */
    private function populateSensoriInstallati($idUtenza) {
        return $this->getSensoriByUtenza($idUtenza);
    }

    /**
     *
     */
    private function populateEdifici($idUtenza) {

        $edifici = [];
        $unitaImmobiliari = $this->getUnitaImmobiliariByUtenza($idUtenza);

        // Prepara la mappa che associa l'edificio all'unità immobiliare
        $edificioUnita = [];
        foreach ($unitaImmobiliari as $ui) {
            $edificioUnita[$ui['id_edificio']][] = $ui['numero_contatore'];
        }

        // Popola il campo unità_immobiliari dell'edificio
        foreach ($edificioUnita as $edificioId => $numeriContatore) {
            $edificio = $this->getEdificio($edificioId);

            $edificio['unita_immobiliari'] = [];
            foreach ($numeriContatore as $numeroContatore) {
                $unitaImmobiliare = $unitaImmobiliari[$numeroContatore];

                $zona = $this->getZonaByNumeroContatore($numeroContatore);
                $zona['illuminazione'] = $this->getIlluminazioneByZona($zona->id);

                $unitaImmobiliare['zona'] = $zona;
                $edificio['unita_immobiliari'][] = $unitaImmobiliare;
                //$edificio['unita_immobiliari'][] = $unitaImmobiliari[$numeroContatore];
            }

            $edifici[] = $edificio;
        }



        return $edifici;
    }

    /**
     *
     */
    public function getProfile($idUtenza) {

        $utenza = $this->getUtenza($idUtenza);

        if (!$utenza)
            return false;

        $retval = $utenza;

        //
        $retval->edifici = $this->populateEdifici($utenza->id_utenza);
        //
        $retval->sensori_installati = $this->populateSensoriInstallati($utenza->id_utenza);
        //
        $retval->storico_consumi = $this->populateStoricoConsumi($utenza->codice_fiscale);


        return $retval;
    }

    /**
     *
     */
    private function prepareOpenTSDBQueryString(array $args = array()) {

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

    /*
    *
    */
    public function getSensoreDataByNumeroContatore($numero_contatore, $metrica, $canale = 1, array $queryParams = array())
    {
        $defaultParams = [
            'start' => '1h-ago',
            'end' => time(),
            'aggregator' => 'sum',
            'downsample' => ''
        ];
        $queryParams = array_merge($defaultParams, $queryParams);

        $q = $this->prepareOpenTSDBQueryString(
            array_merge($queryParams, [
                'metric' => $metrica,
                'tags' => [
                    'utenza' => $numero_contatore,
                    'canale' => $canale
                ]
            ])
        );

        $url = 'http://' . Config::SIDECO_HOST . ':' . Config::OPENTSDB_PORT . "/api/query/{$q}";

        $data = @file_get_contents($url);

        return $data ? json_decode($data) : false;
    }
}
