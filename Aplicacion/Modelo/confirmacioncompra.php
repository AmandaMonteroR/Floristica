<?php

class Confirmacioncompra
{
    private $tbconfirmacioncompraid;
    private $tbpedidoid;
    private $tbconfirmacioncomprafecha;
    private $tbconfirmacioncompratipopago;
    private $tbconfirmacioncompramonto;
    private $tbconfirmacioncompraplazodias;

    public function __construct($datos = [])
    {
        $this->tbconfirmacioncompraid        = $datos['tbconfirmacioncompraid'] ?? null;
        $this->tbpedidoid                    = $datos['tbpedidoid'] ?? 0;
        $this->tbconfirmacioncomprafecha     = $datos['tbconfirmacioncomprafecha'] ?? null;
        $this->tbconfirmacioncompratipopago  = $datos['tbconfirmacioncompratipopago'] ?? '';
        $this->tbconfirmacioncompramonto     = $datos['tbconfirmacioncompramonto'] ?? 0;
        $this->tbconfirmacioncompraplazodias = $datos['tbconfirmacioncompraplazodias'] ?? 0;
    }

    public function getTbconfirmacioncompraid() { 
        return $this->tbconfirmacioncompraid; }

    public function setTbconfirmacioncompraid($valor) { 
        $this->tbconfirmacioncompraid = $valor; }

    public function getTbpedidoid() { 
        return $this->tbpedidoid; }

    public function setTbpedidoid($valor) { 
        $this->tbpedidoid = $valor; }

    public function getTbconfirmacioncomprafecha() { 
        return $this->tbconfirmacioncomprafecha; }

    public function setTbconfirmacioncomprafecha($valor) { 
        $this->tbconfirmacioncomprafecha = $valor; }

    public function getTbconfirmacioncompratipopago() { 
        return $this->tbconfirmacioncompratipopago; }

    public function setTbconfirmacioncompratipopago($valor) { 
        $this->tbconfirmacioncompratipopago = $valor; }

    public function getTbconfirmacioncompramonto() { 
        return $this->tbconfirmacioncompramonto; }

    public function setTbconfirmacioncompramonto($valor) { 
        $this->tbconfirmacioncompramonto = $valor; }

    public function getTbconfirmacioncompraplazodias() { 
        return $this->tbconfirmacioncompraplazodias; }

    public function setTbconfirmacioncompraplazodias($valor) { 
        $this->tbconfirmacioncompraplazodias = $valor; }
}