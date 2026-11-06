<?php

/**
 * Acceso a una opción de wp_options (mismo patrón que DPV_USE_DATA_BASE).
 */
class OEP_USE_DATA_BASE
{
    protected $KEY = '';
    protected $AUTOLOAD = false;
    protected $DATA = [];

    public function __construct()
    {
        $this->DATA = get_option($this->KEY, []);
        if (!is_array($this->DATA)) {
            $this->DATA = [];
        }
    }

    protected function save()
    {
        // La configuración solo se usa en el admin y al enviar correos: no necesita autoload.
        update_option($this->KEY, $this->DATA, $this->AUTOLOAD);
    }

    public function get()
    {
        return $this->DATA;
    }
}
