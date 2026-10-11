<?php

class Pedidodetalle
{
    private $tbpedidodetalleid;
    private $tbpedidoid;
    private $tbinventarioid;
    private $tbpedidodetallecantidad;
    private $tbpedidodetalleprecio;

    public function __construct($datos = [])
    {
        $this->tbpedidodetalleid       = $datos['tbpedidodetalleid'] ?? null;
        $this->tbpedidoid              = $datos['tbpedidoid'] ?? 0;
        $this->tbinventarioid          = $datos['tbinventarioid'] ?? 0;
        $this->tbpedidodetallecantidad = $datos['tbpedidodetallecantidad'] ?? 0;
        $this->tbpedidodetalleprecio   = $datos['tbpedidodetalleprecio'] ?? 0;
    }

    public function getTbpedidodetalleid() { 
        return $this->tbpedidodetalleid; }

    public function setTbpedidodetalleid($valor) { 
        $this->tbpedidodetalleid = $valor; }

    public function getTbpedidoid() { 
        return $this->tbpedidoid; }

    public function setTbpedidoid($valor) { 
        $this->tbpedidoid = $valor; }

    public function getTbinventarioid() { 
        return $this->tbinventarioid; }

    public function setTbinventarioid($valor) { 
        $this->tbinventarioid = $valor; }

    public function getTbpedidodetallecantidad() { 
        return $this->tbpedidodetallecantidad; }

    public function setTbpedidodetallecantidad($valor) { 
        $this->tbpedidodetallecantidad = $valor; }

    public function getTbpedidodetalleprecio() { 
        return $this->tbpedidodetalleprecio; }

    public function setTbpedidodetalleprecio($valor) { 
        $this->tbpedidodetalleprecio = $valor; }
}