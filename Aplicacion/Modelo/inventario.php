<?php

class Inventario
{
    private $tbinventarioid;
    private $tbproveedorid;
    private $tbinventarionombre;
    private $tbinventariocategoria;
    private $tbinventariopreciopaquete;
    private $tbinventariounidadespaquete;
    private $tbinventariopreciounidad;
    private $tbinventariostock;
    private $tbinventariostockminimo;
    private $tbinventarioestado;

    public function __construct($datos = [])
    {
        $this->tbinventarioid              = $datos['tbinventarioid'] ?? null;
        $this->tbproveedorid               = $datos['tbproveedorid'] ?? 0;
        $this->tbinventarionombre          = $datos['tbinventarionombre'] ?? '';
        $this->tbinventariocategoria       = $datos['tbinventariocategoria'] ?? '';
        $this->tbinventariopreciopaquete   = $datos['tbinventariopreciopaquete'] ?? 0;
        $this->tbinventariounidadespaquete = $datos['tbinventariounidadespaquete'] ?? 0;
        $this->tbinventariopreciounidad    = $datos['tbinventariopreciounidad'] ?? 0;
        $this->tbinventariostock           = $datos['tbinventariostock'] ?? 0;
        $this->tbinventariostockminimo     = $datos['tbinventariostockminimo'] ?? 0;
        $this->tbinventarioestado          = $datos['tbinventarioestado'] ?? 1;
    }

    public function getTbinventarioid() { 
        return $this->tbinventarioid; }

    public function setTbinventarioid($valor) { 
        $this->tbinventarioid = $valor; }

    public function getTbproveedorid() { 
        return $this->tbproveedorid; }

    public function setTbproveedorid($valor) { 
        $this->tbproveedorid = $valor; }

    public function getTbinventarionombre() { 
        return $this->tbinventarionombre; }

    public function setTbinventarionombre($valor) { 
        $this->tbinventarionombre = $valor; }

    public function getTbinventariocategoria() { 
        return $this->tbinventariocategoria; }

    public function setTbinventariocategoria($valor) { 
        $this->tbinventariocategoria = $valor; }

    public function getTbinventariopreciopaquete() { 
        return $this->tbinventariopreciopaquete; }

    public function setTbinventariopreciopaquete($valor) { 
        $this->tbinventariopreciopaquete = $valor; }

    public function getTbinventariounidadespaquete() { 
        return $this->tbinventariounidadespaquete; }

    public function setTbinventariounidadespaquete($valor) { 
        $this->tbinventariounidadespaquete = $valor; }

    public function getTbinventariopreciounidad() { 
        return $this->tbinventariopreciounidad; }

    public function setTbinventariopreciounidad($valor) { 
        $this->tbinventariopreciounidad = $valor; }

    public function getTbinventariostock() { 
        return $this->tbinventariostock; }

    public function setTbinventariostock($valor) { 
        $this->tbinventariostock = $valor; }

    public function getTbinventariostockminimo() { 
        return $this->tbinventariostockminimo; }

    public function setTbinventariostockminimo($valor) { 
        $this->tbinventariostockminimo = $valor; }

    public function getTbinventarioestado() { 
        return $this->tbinventarioestado; }

    public function setTbinventarioestado($valor) { 
        $this->tbinventarioestado = $valor; }

    public function estaActivo()
    {
        return (int) $this->tbinventarioestado === 1;
    }

    public function tieneStockBajo()
    {
        return (int) $this->tbinventariostock <= (int) $this->tbinventariostockminimo;
    }
}