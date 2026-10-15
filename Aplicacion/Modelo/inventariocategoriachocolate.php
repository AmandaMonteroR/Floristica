<?php

class Inventariocategoriachocolate
{
    private $tbinventariocategoriachocolateid;
    private $tbinventarioid;
    private $tbinventariocategoriachocolatetipo;
    private $tbinventariocategoriachocolatemarca;
    private $tbinventariocategoriachocolatepresentacion;
    private $tbinventariocategoriachocolatepeso;
    private $tbinventariocategoriachocolatesabor;
    private $tbinventariocategoriachocolatefechavencimiento;

    public function __construct($datos = [])
    {
        $this->tbinventariocategoriachocolateid               = $datos['tbinventariocategoriachocolateid'] ?? null;
        $this->tbinventarioid                                 = $datos['tbinventarioid'] ?? 0;
        $this->tbinventariocategoriachocolatetipo             = $datos['tbinventariocategoriachocolatetipo'] ?? '';
        $this->tbinventariocategoriachocolatemarca            = $datos['tbinventariocategoriachocolatemarca'] ?? '';
        $this->tbinventariocategoriachocolatepresentacion     = $datos['tbinventariocategoriachocolatepresentacion'] ?? '';
        $this->tbinventariocategoriachocolatepeso             = $datos['tbinventariocategoriachocolatepeso'] ?? 0;
        $this->tbinventariocategoriachocolatesabor            = $datos['tbinventariocategoriachocolatesabor'] ?? '';
        $this->tbinventariocategoriachocolatefechavencimiento = $datos['tbinventariocategoriachocolatefechavencimiento'] ?? null;
    }

    public function getTbinventariocategoriachocolateid() { 
        return $this->tbinventariocategoriachocolateid; }

    public function setTbinventariocategoriachocolateid($valor) { 
        $this->tbinventariocategoriachocolateid = $valor; }

    public function getTbinventarioid() { 
        return $this->tbinventarioid; }

    public function setTbinventarioid($valor) { 
        $this->tbinventarioid = $valor; }

    public function getTbinventariocategoriachocolatetipo() { 
        return $this->tbinventariocategoriachocolatetipo; }

    public function setTbinventariocategoriachocolatetipo($valor) { 
        $this->tbinventariocategoriachocolatetipo = $valor; }

    public function getTbinventariocategoriachocolatemarca() { 
        return $this->tbinventariocategoriachocolatemarca; }

    public function setTbinventariocategoriachocolatemarca($valor) { 
        $this->tbinventariocategoriachocolatemarca = $valor; }

    public function getTbinventariocategoriachocolatepresentacion() { 
        return $this->tbinventariocategoriachocolatepresentacion; }

    public function setTbinventariocategoriachocolatepresentacion($valor) { 
        $this->tbinventariocategoriachocolatepresentacion = $valor; }

    public function getTbinventariocategoriachocolatepeso() { 
        return $this->tbinventariocategoriachocolatepeso; }

    public function setTbinventariocategoriachocolatepeso($valor) { 
        $this->tbinventariocategoriachocolatepeso = $valor; }

    public function getTbinventariocategoriachocolatesabor() { 
        return $this->tbinventariocategoriachocolatesabor; }

    public function setTbinventariocategoriachocolatesabor($valor) { 
        $this->tbinventariocategoriachocolatesabor = $valor; }

    public function getTbinventariocategoriachocolatefechavencimiento() { 
        return $this->tbinventariocategoriachocolatefechavencimiento; }

    public function setTbinventariocategoriachocolatefechavencimiento($valor) { 
        $this->tbinventariocategoriachocolatefechavencimiento = $valor; }
}
