<?php

// Solicitud de encargo: predefinida (arreglo del catálogo + adicionales)
// o personalizada (productos que elige el cliente, con presupuesto o libre)
class Encargo
{
    private $tbsolicitudencargoid;
    private $tbclienteid;
    private $tbempleadoid;
    private $tbsolicitudencargotipo;
    private $tbarreglofloralid;
    private $tbsolicitudencargocantidad;
    private $tbsolicitudencargoprecioarreglo;
    private $tbsolicitudencargomodalidad;
    private $tbsolicitudencargopresupuesto;
    private $tbsolicitudencargoexpress;
    private $tbzonaentregaid;
    private $tbsolicitudencargodireccionentrega;
    private $tbsolicitudencargocostoexpress;
    private $tbsolicitudencargosubtotal;
    private $tbsolicitudencargototal;
    private $tbsolicitudencargofechaentrega;
    private $tbsolicitudencargonota;
    private $tbsolicitudencargoestado;
    private $tbsolicitudencargofecharegistro;

    public function __construct($datos = [])
    {
        $this->tbsolicitudencargoid               = $datos['tbsolicitudencargoid'] ?? null;
        $this->tbclienteid                        = $datos['tbclienteid'] ?? null;
        $this->tbempleadoid                       = $datos['tbempleadoid'] ?? null;
        $this->tbsolicitudencargotipo             = $datos['tbsolicitudencargotipo'] ?? 'Predefinido';
        $this->tbarreglofloralid                  = $datos['tbarreglofloralid'] ?? null;
        $this->tbsolicitudencargocantidad         = $datos['tbsolicitudencargocantidad'] ?? 1;
        $this->tbsolicitudencargoprecioarreglo    = $datos['tbsolicitudencargoprecioarreglo'] ?? null;
        $this->tbsolicitudencargomodalidad        = $datos['tbsolicitudencargomodalidad'] ?? null;
        $this->tbsolicitudencargopresupuesto      = $datos['tbsolicitudencargopresupuesto'] ?? null;
        $this->tbsolicitudencargoexpress          = $datos['tbsolicitudencargoexpress'] ?? 0;
        $this->tbzonaentregaid                    = $datos['tbzonaentregaid'] ?? null;
        $this->tbsolicitudencargodireccionentrega = $datos['tbsolicitudencargodireccionentrega'] ?? '';
        $this->tbsolicitudencargocostoexpress     = $datos['tbsolicitudencargocostoexpress'] ?? 0;
        $this->tbsolicitudencargosubtotal         = $datos['tbsolicitudencargosubtotal'] ?? 0;
        $this->tbsolicitudencargototal            = $datos['tbsolicitudencargototal'] ?? 0;
        $this->tbsolicitudencargofechaentrega     = $datos['tbsolicitudencargofechaentrega'] ?? null;
        $this->tbsolicitudencargonota             = $datos['tbsolicitudencargonota'] ?? '';
        $this->tbsolicitudencargoestado           = $datos['tbsolicitudencargoestado'] ?? 'Pendiente';
        $this->tbsolicitudencargofecharegistro    = $datos['tbsolicitudencargofecharegistro'] ?? null;
    }

    public function getTbsolicitudencargoid() {
        return $this->tbsolicitudencargoid; }

    public function setTbsolicitudencargoid($valor) {
        $this->tbsolicitudencargoid = $valor; }

    public function getTbclienteid() {
        return $this->tbclienteid; }

    public function setTbclienteid($valor) {
        $this->tbclienteid = $valor; }

    public function getTbempleadoid() {
        return $this->tbempleadoid; }

    public function setTbempleadoid($valor) {
        $this->tbempleadoid = $valor; }

    public function getTbsolicitudencargotipo() {
        return $this->tbsolicitudencargotipo; }

    public function setTbsolicitudencargotipo($valor) {
        $this->tbsolicitudencargotipo = $valor; }

    public function getTbarreglofloralid() {
        return $this->tbarreglofloralid; }

    public function setTbarreglofloralid($valor) {
        $this->tbarreglofloralid = $valor; }

    public function getTbsolicitudencargocantidad() {
        return $this->tbsolicitudencargocantidad; }

    public function setTbsolicitudencargocantidad($valor) {
        $this->tbsolicitudencargocantidad = $valor; }

    public function getTbsolicitudencargoprecioarreglo() {
        return $this->tbsolicitudencargoprecioarreglo; }

    public function setTbsolicitudencargoprecioarreglo($valor) {
        $this->tbsolicitudencargoprecioarreglo = $valor; }

    public function getTbsolicitudencargomodalidad() {
        return $this->tbsolicitudencargomodalidad; }

    public function setTbsolicitudencargomodalidad($valor) {
        $this->tbsolicitudencargomodalidad = $valor; }

    public function getTbsolicitudencargopresupuesto() {
        return $this->tbsolicitudencargopresupuesto; }

    public function setTbsolicitudencargopresupuesto($valor) {
        $this->tbsolicitudencargopresupuesto = $valor; }

    public function getTbsolicitudencargoexpress() {
        return $this->tbsolicitudencargoexpress; }

    public function setTbsolicitudencargoexpress($valor) {
        $this->tbsolicitudencargoexpress = $valor; }

    public function getTbzonaentregaid() {
        return $this->tbzonaentregaid; }

    public function setTbzonaentregaid($valor) {
        $this->tbzonaentregaid = $valor; }

    public function getTbsolicitudencargodireccionentrega() {
        return $this->tbsolicitudencargodireccionentrega; }

    public function setTbsolicitudencargodireccionentrega($valor) {
        $this->tbsolicitudencargodireccionentrega = $valor; }

    public function getTbsolicitudencargocostoexpress() {
        return $this->tbsolicitudencargocostoexpress; }

    public function setTbsolicitudencargocostoexpress($valor) {
        $this->tbsolicitudencargocostoexpress = $valor; }

    public function getTbsolicitudencargosubtotal() {
        return $this->tbsolicitudencargosubtotal; }

    public function setTbsolicitudencargosubtotal($valor) {
        $this->tbsolicitudencargosubtotal = $valor; }

    public function getTbsolicitudencargototal() {
        return $this->tbsolicitudencargototal; }

    public function setTbsolicitudencargototal($valor) {
        $this->tbsolicitudencargototal = $valor; }

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

    public function esPersonalizado()
    {
        return $this->tbsolicitudencargotipo === 'Personalizado';
    }

    public function tieneExpress()
    {
        return (int) $this->tbsolicitudencargoexpress === 1;
    }
}
