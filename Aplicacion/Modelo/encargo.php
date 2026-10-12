<?php

class Encargo
{
    private $tbencargoid;
    private $tbarreglofloralid;
    private $tbempleadoid;
    private $tbencargocantidad;
    private $tbencargofechaentrega;
    private $tbencargonota;
    private $tbencargoestado;
    private $tbencargofecharegistro;

    public function __construct($datos = [])
    {
        $this->tbencargoid            = $datos['tbencargoid'] ?? null;
        $this->tbarreglofloralid      = $datos['tbarreglofloralid'] ?? 0;
        $this->tbempleadoid           = $datos['tbempleadoid'] ?? null;
        $this->tbencargocantidad      = $datos['tbencargocantidad'] ?? 1;
        $this->tbencargofechaentrega  = $datos['tbencargofechaentrega'] ?? null;
        $this->tbencargonota          = $datos['tbencargonota'] ?? '';
        $this->tbencargoestado        = $datos['tbencargoestado'] ?? 'Pendiente';
        $this->tbencargofecharegistro = $datos['tbencargofecharegistro'] ?? null;
    }

    public function getTbencargoid() {
        return $this->tbencargoid; }

    public function setTbencargoid($valor) {
        $this->tbencargoid = $valor; }

    public function getTbarreglofloralid() {
        return $this->tbarreglofloralid; }

    public function setTbarreglofloralid($valor) {
        $this->tbarreglofloralid = $valor; }

    public function getTbempleadoid() {
        return $this->tbempleadoid; }

    public function setTbempleadoid($valor) {
        $this->tbempleadoid = $valor; }

    public function getTbencargocantidad() {
        return $this->tbencargocantidad; }

    public function setTbencargocantidad($valor) {
        $this->tbencargocantidad = $valor; }

    public function getTbencargofechaentrega() {
        return $this->tbencargofechaentrega; }

    public function setTbencargofechaentrega($valor) {
        $this->tbencargofechaentrega = $valor; }

    public function getTbencargonota() {
        return $this->tbencargonota; }

    public function setTbencargonota($valor) {
        $this->tbencargonota = $valor; }

    public function getTbencargoestado() {
        return $this->tbencargoestado; }

    public function setTbencargoestado($valor) {
        $this->tbencargoestado = $valor; }

    public function getTbencargofecharegistro() {
        return $this->tbencargofecharegistro; }

    public function setTbencargofecharegistro($valor) {
        $this->tbencargofecharegistro = $valor; }

    public function estaAbierto()
    {
        return in_array($this->tbencargoestado, ['Pendiente', 'En proceso'], true);
    }
}
