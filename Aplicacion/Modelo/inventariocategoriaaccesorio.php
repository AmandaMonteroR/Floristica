<?php

// Accesorio que se le agrega a un arreglo: corona, perfume, splash, moño, topper...
class Inventariocategoriaaccesorio
{
    private $tbinventariocategoriaaccesorioid;
    private $tbinventarioid;
    private $tbinventariocategoriaaccesoriotipo;
    private $tbinventariocategoriaaccesoriotamano;
    private $tbinventariocategoriaaccesoriocolor;
    private $tbinventariocategoriaaccesoriomaterial;

    public function __construct($datos = [])
    {
        $this->tbinventariocategoriaaccesorioid       = $datos['tbinventariocategoriaaccesorioid'] ?? null;
        $this->tbinventarioid                         = $datos['tbinventarioid'] ?? 0;
        $this->tbinventariocategoriaaccesoriotipo     = $datos['tbinventariocategoriaaccesoriotipo'] ?? '';
        $this->tbinventariocategoriaaccesoriotamano   = $datos['tbinventariocategoriaaccesoriotamano'] ?? '';
        $this->tbinventariocategoriaaccesoriocolor    = $datos['tbinventariocategoriaaccesoriocolor'] ?? null;
        $this->tbinventariocategoriaaccesoriomaterial = $datos['tbinventariocategoriaaccesoriomaterial'] ?? null;
    }

    public function getTbinventariocategoriaaccesorioid() {
        return $this->tbinventariocategoriaaccesorioid; }

    public function setTbinventariocategoriaaccesorioid($valor) {
        $this->tbinventariocategoriaaccesorioid = $valor; }

    public function getTbinventarioid() {
        return $this->tbinventarioid; }

    public function setTbinventarioid($valor) {
        $this->tbinventarioid = $valor; }

    public function getTbinventariocategoriaaccesoriotipo() {
        return $this->tbinventariocategoriaaccesoriotipo; }

    public function setTbinventariocategoriaaccesoriotipo($valor) {
        $this->tbinventariocategoriaaccesoriotipo = $valor; }

    public function getTbinventariocategoriaaccesoriotamano() {
        return $this->tbinventariocategoriaaccesoriotamano; }

    public function setTbinventariocategoriaaccesoriotamano($valor) {
        $this->tbinventariocategoriaaccesoriotamano = $valor; }

    public function getTbinventariocategoriaaccesoriocolor() {
        return $this->tbinventariocategoriaaccesoriocolor; }

    public function setTbinventariocategoriaaccesoriocolor($valor) {
        $this->tbinventariocategoriaaccesoriocolor = $valor; }

    public function getTbinventariocategoriaaccesoriomaterial() {
        return $this->tbinventariocategoriaaccesoriomaterial; }

    public function setTbinventariocategoriaaccesoriomaterial($valor) {
        $this->tbinventariocategoriaaccesoriomaterial = $valor; }
}
