<?php

class Pedido
{
    private $tbpedidoid;
    private $tbproveedorid;
    private $tbpedidofecha;
    private $tbpedidoestado;

    public function __construct($datos = [])
    {
        $this->tbpedidoid     = $datos['tbpedidoid'] ?? null;
        $this->tbproveedorid  = $datos['tbproveedorid'] ?? 0;
        $this->tbpedidofecha  = $datos['tbpedidofecha'] ?? null;
        $this->tbpedidoestado = $datos['tbpedidoestado'] ?? 'Pendiente';
    }

    public function getTbpedidoid() { 
        return $this->tbpedidoid; }

    public function setTbpedidoid($valor) { 
        $this->tbpedidoid = $valor; }

    public function getTbproveedorid() { 
        return $this->tbproveedorid; }

    public function setTbproveedorid($valor) { 
        $this->tbproveedorid = $valor; }

    public function getTbpedidofecha() { 
        return $this->tbpedidofecha; }

    public function setTbpedidofecha($valor) { 
        $this->tbpedidofecha = $valor; }

    public function getTbpedidoestado() { 
        return $this->tbpedidoestado; }

    public function setTbpedidoestado($valor) { 
        $this->tbpedidoestado = $valor; }
}