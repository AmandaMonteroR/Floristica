<?php

class Arreglofloralinsumo
{
    private $tbarreglofloralinsumoid;
    private $tbarreglofloralid;
    private $tbinventarioid;
    private $tbarreglofloralinsumocantidad;

    public function __construct($datos = [])
    {
        $this->tbarreglofloralinsumoid       = $datos['tbarreglofloralinsumoid'] ?? null;
        $this->tbarreglofloralid              = $datos['tbarreglofloralid'] ?? 0;
        $this->tbinventarioid                 = $datos['tbinventarioid'] ?? 0;
        $this->tbarreglofloralinsumocantidad = $datos['tbarreglofloralinsumocantidad'] ?? 0;
    }

    public function getTbarreglofloralinsumoid() { 
        return $this->tbarreglofloralinsumoid; }

    public function setTbarreglofloralinsumoid($valor) { 
        $this->tbarreglofloralinsumoid = $valor; }

    public function getTbarreglofloralid() { 
        return $this->tbarreglofloralid; }

    public function setTbarreglofloralid($valor) { 
        $this->tbarreglofloralid = $valor; }

    public function getTbinventarioid() { 
        return $this->tbinventarioid; }

    public function setTbinventarioid($valor) { 
        $this->tbinventarioid = $valor; }

    public function getTbarreglofloralinsumocantidad() { 
        return $this->tbarreglofloralinsumocantidad; }

    public function setTbarreglofloralinsumocantidad($valor) { 
        $this->tbarreglofloralinsumocantidad = $valor; }
}