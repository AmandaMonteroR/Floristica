<?php

class Encargo
{
    private $tbsolicitudencargoid;
    private $tbarreglofloralid;
    private $tbempleadoid;
    private $tbsolicitudencargocantidad;
    private $tbsolicitudencargofechaentrega;
    private $tbsolicitudencargonota;
    private $tbsolicitudencargoestado;
    private $tbsolicitudencargofecharegistro;

    public function __construct($datos = [])
    {
        $this->tbsolicitudencargoid            = $datos['tbsolicitudencargoid'] ?? null;
        $this->tbarreglofloralid      = $datos['tbarreglofloralid'] ?? 0;
        $this->tbempleadoid           = $datos['tbempleadoid'] ?? null;
        $this->tbsolicitudencargocantidad      = $datos['tbsolicitudencargocantidad'] ?? 1;
        $this->tbsolicitudencargofechaentrega  = $datos['tbsolicitudencargofechaentrega'] ?? null;
        $this->tbsolicitudencargonota          = $datos['tbsolicitudencargonota'] ?? '';
        $this->tbsolicitudencargoestado        = $datos['tbsolicitudencargoestado'] ?? 'Pendiente';
        $this->tbsolicitudencargofecharegistro = $datos['tbsolicitudencargofecharegistro'] ?? null;
    }

    public function getTbsolicitudencargoid() {
        return $this->tbsolicitudencargoid; }

    public function setTbsolicitudencargoid($valor) {
        $this->tbsolicitudencargoid = $valor; }

    public function getTbarreglofloralid() {
        return $this->tbarreglofloralid; }

    public function setTbarreglofloralid($valor) {
        $this->tbarreglofloralid = $valor; }

    public function getTbempleadoid() {
        return $this->tbempleadoid; }

    public function setTbempleadoid($valor) {
        $this->tbempleadoid = $valor; }

    public function getTbsolicitudencargocantidad() {
        return $this->tbsolicitudencargocantidad; }

    public function setTbsolicitudencargocantidad($valor) {
        $this->tbsolicitudencargocantidad = $valor; }

    public function getTbsolicitudencargofechaentrega() {
        return $this->tbsolicitudencargofechaentrega; }

    public function setTbsolicitudencargofechaentrega($valor) {
        $this->tbsolicitudencargofechaentrega = $valor; }

    public function getTbsolicitudencargonota() {
        return $this->tbsolicitudencargonota; }

    public function setTbsolicitudencargonota($valor) {
        $this->tbsolicitudencargonota = $valor; }

    public function getTbsolicitudencargoestado() {
        return $this->tbsolicitudencargoestado; }

    public function setTbsolicitudencargoestado($valor) {
        $this->tbsolicitudencargoestado = $valor; }

    public function getTbsolicitudencargofecharegistro() {
        return $this->tbsolicitudencargofecharegistro; }

    public function setTbsolicitudencargofecharegistro($valor) {
        $this->tbsolicitudencargofecharegistro = $valor; }

    public function estaAbierto()
    {
        return in_array($this->tbsolicitudencargoestado, ['Pendiente', 'En proceso'], true);
    }
}
