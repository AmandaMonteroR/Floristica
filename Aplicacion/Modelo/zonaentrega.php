<?php

// Zona a la que se lleva un encargo con servicio express, con su precio
class Zonaentrega
{
    private $tbzonaentregaid;
    private $tbzonaentreganombre;
    private $tbzonaentregadescripcion;
    private $tbzonaentregaprecio;
    private $tbzonaentregaestado;

    public function __construct($datos = [])
    {
        $this->tbzonaentregaid          = $datos['tbzonaentregaid'] ?? null;
        $this->tbzonaentreganombre      = $datos['tbzonaentreganombre'] ?? '';
        $this->tbzonaentregadescripcion = $datos['tbzonaentregadescripcion'] ?? '';
        $this->tbzonaentregaprecio      = $datos['tbzonaentregaprecio'] ?? 0;
        $this->tbzonaentregaestado      = $datos['tbzonaentregaestado'] ?? 1;
    }

    public function getTbzonaentregaid() {
        return $this->tbzonaentregaid; }

    public function setTbzonaentregaid($valor) {
        $this->tbzonaentregaid = $valor; }

    public function getTbzonaentreganombre() {
        return $this->tbzonaentreganombre; }

    public function setTbzonaentreganombre($valor) {
        $this->tbzonaentreganombre = $valor; }

    public function getTbzonaentregadescripcion() {
        return $this->tbzonaentregadescripcion; }

    public function setTbzonaentregadescripcion($valor) {
        $this->tbzonaentregadescripcion = $valor; }

    public function getTbzonaentregaprecio() {
        return $this->tbzonaentregaprecio; }

    public function setTbzonaentregaprecio($valor) {
        $this->tbzonaentregaprecio = $valor; }

    public function getTbzonaentregaestado() {
        return $this->tbzonaentregaestado; }

    public function setTbzonaentregaestado($valor) {
        $this->tbzonaentregaestado = $valor; }

    public function estaActivo()
    {
        return (int) $this->tbzonaentregaestado === 1;
    }
}
