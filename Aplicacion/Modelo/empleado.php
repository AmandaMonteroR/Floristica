<?php

class Empleado
{
    private $tbempleadoid;
    private $tbempleadonombre;
    private $tbempleadotelefono;
    private $tbempleadorol;
    private $tbempleadoestado;
    private $tbempleadousuario;
    private $tbempleadoclave;

    public function __construct($datos = [])
    {
        $this->tbempleadoid       = $datos['tbempleadoid'] ?? null;
        $this->tbempleadonombre   = $datos['tbempleadonombre'] ?? '';
        $this->tbempleadotelefono = $datos['tbempleadotelefono'] ?? '';
        $this->tbempleadorol      = $datos['tbempleadorol'] ?? 'Empleado';
        $this->tbempleadoestado   = $datos['tbempleadoestado'] ?? 1;
        $this->tbempleadousuario  = $datos['tbempleadousuario'] ?? '';
        $this->tbempleadoclave    = $datos['tbempleadoclave'] ?? '';
    }

    public function getTbempleadoid() {
        return $this->tbempleadoid; }

    public function setTbempleadoid($valor) {
        $this->tbempleadoid = $valor; }

    public function getTbempleadonombre() {
        return $this->tbempleadonombre; }

    public function setTbempleadonombre($valor) {
        $this->tbempleadonombre = $valor; }

    public function getTbempleadotelefono() {
        return $this->tbempleadotelefono; }

    public function setTbempleadotelefono($valor) {
        $this->tbempleadotelefono = $valor; }

    public function getTbempleadorol() {
        return $this->tbempleadorol; }

    public function setTbempleadorol($valor) {
        $this->tbempleadorol = $valor; }

    public function getTbempleadoestado() {
        return $this->tbempleadoestado; }

    public function setTbempleadoestado($valor) {
        $this->tbempleadoestado = $valor; }

        public function getTbempleadousuario() {
        return $this->tbempleadousuario; }

    public function setTbempleadousuario($valor) {
        $this->tbempleadousuario = $valor; }

    public function getTbempleadoclave() {
        return $this->tbempleadoclave; }

    public function setTbempleadoclave($valor) {
        $this->tbempleadoclave = $valor; }

    public function tieneCuenta()
    {
        return (string) $this->tbempleadousuario !== '' && (string) $this->tbempleadoclave !== '';
    }    

    public function estaActivo()
    {
        return (int) $this->tbempleadoestado === 1;
    }

    public function esDuena()
    {
        return $this->tbempleadorol === 'Dueña';
    }
}