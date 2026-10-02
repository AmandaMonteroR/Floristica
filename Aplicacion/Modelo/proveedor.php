<?php

class Proveedor
{
    private $tbproveedorid;
    private $tbproveedornombreempresa;
    private $tbproveedornombrecontacto;
    private $tbproveedortelefono;
    private $tbproveedorcorreo;
    private $tbproveedorprovincia;
    private $tbproveedorcanton;
    private $tbproveedordistrito;
    private $tbproveedordireccion;
    private $tbproveedordescripcion;
    private $tbproveedorestado;
    private $tbproveedorfecharegistro;

    public function __construct($datos = [])
    {
        $this->tbproveedorid             = $datos['tbproveedorid'] ?? null;
        $this->tbproveedornombreempresa  = $datos['tbproveedornombreempresa'] ?? '';
        $this->tbproveedornombrecontacto = $datos['tbproveedornombrecontacto'] ?? '';
        $this->tbproveedortelefono       = $datos['tbproveedortelefono'] ?? '';
        $this->tbproveedorcorreo         = $datos['tbproveedorcorreo'] ?? '';
        $this->tbproveedorprovincia      = $datos['tbproveedorprovincia'] ?? '';
        $this->tbproveedorcanton         = $datos['tbproveedorcanton'] ?? '';
        $this->tbproveedordistrito       = $datos['tbproveedordistrito'] ?? '';
        $this->tbproveedordireccion      = $datos['tbproveedordireccion'] ?? '';
        $this->tbproveedordescripcion    = $datos['tbproveedordescripcion'] ?? '';
        $this->tbproveedorestado         = $datos['tbproveedorestado'] ?? 1;
        $this->tbproveedorfecharegistro  = $datos['tbproveedorfecharegistro'] ?? null;
    }

    public function getTbproveedorid() { 
        return $this->tbproveedorid; }

    public function setTbproveedorid($valor) { 
        $this->tbproveedorid = $valor; }

    public function getTbproveedornombreempresa() { 
        return $this->tbproveedornombreempresa; }

    public function setTbproveedornombreempresa($valor) { 
        $this->tbproveedornombreempresa = $valor; }

    public function getTbproveedornombrecontacto() { 
        return $this->tbproveedornombrecontacto; }

    public function setTbproveedornombrecontacto($valor) { 
        $this->tbproveedornombrecontacto = $valor; }

    public function getTbproveedortelefono() { 
        return $this->tbproveedortelefono; }

    public function setTbproveedortelefono($valor) { 
        $this->tbproveedortelefono = $valor; }

    public function getTbproveedorcorreo() { 
        return $this->tbproveedorcorreo; }

    public function setTbproveedorcorreo($valor) { 
        $this->tbproveedorcorreo = $valor; }

    public function getTbproveedorprovincia() { 
        return $this->tbproveedorprovincia; }

    public function setTbproveedorprovincia($valor) { 
        $this->tbproveedorprovincia = $valor; }

    public function getTbproveedorcanton() { 
        return $this->tbproveedorcanton; }

    public function setTbproveedorcanton($valor) { 
        $this->tbproveedorcanton = $valor; }

    public function getTbproveedordistrito() { 
        return $this->tbproveedordistrito; }

    public function setTbproveedordistrito($valor) { 
        $this->tbproveedordistrito = $valor; }

    public function getTbproveedordireccion() { 
        return $this->tbproveedordireccion; }

    public function setTbproveedordireccion($valor) { 
        $this->tbproveedordireccion = $valor; }

    public function getTbproveedordescripcion() { 
        return $this->tbproveedordescripcion; }

    public function setTbproveedordescripcion($valor) { 
        $this->tbproveedordescripcion = $valor; }

    public function getTbproveedorestado() { 
        return $this->tbproveedorestado; }

    public function setTbproveedorestado($valor) { 
        $this->tbproveedorestado = $valor; }

    public function getTbproveedorfecharegistro() { 
        return $this->tbproveedorfecharegistro; }

    public function setTbproveedorfecharegistro($valor) { 
        $this->tbproveedorfecharegistro = $valor; }

    public function estaActivo()
    {
        return (int) $this->tbproveedorestado === 1;
    }
}