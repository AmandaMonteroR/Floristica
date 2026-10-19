<?php

// Solicitud de pedido de arreglo: predefinida (arreglo del catálogo + adicionales)
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
    private $tbtemporadaid;
    private $tbsolicitudencargosprayid;
    private $tbsolicitudencargoflorespintadas;
    private $tbsolicitudencargopreciopintura;
    private $tbsolicitudencargocostopintura;
    private $tbsolicitudencargoporcentaje;
    private $tbsolicitudencargocosto;

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
        $this->tbtemporadaid                      = $datos['tbtemporadaid'] ?? null;
        $this->tbsolicitudencargosprayid          = $datos['tbsolicitudencargosprayid'] ?? null;
        $this->tbsolicitudencargoflorespintadas   = $datos['tbsolicitudencargoflorespintadas'] ?? 0;
        $this->tbsolicitudencargopreciopintura    = $datos['tbsolicitudencargopreciopintura'] ?? 0;
        $this->tbsolicitudencargocostopintura     = $datos['tbsolicitudencargocostopintura'] ?? 0;
        $this->tbsolicitudencargoporcentaje       = $datos['tbsolicitudencargoporcentaje'] ?? null;
        $this->tbsolicitudencargocosto            = $datos['tbsolicitudencargocosto'] ?? 0;
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

    public function getTbtemporadaid() {
        return $this->tbtemporadaid; }

    public function setTbtemporadaid($valor) {
        $this->tbtemporadaid = $valor; }

    public function getTbsolicitudencargosprayid() {
        return $this->tbsolicitudencargosprayid; }

    public function setTbsolicitudencargosprayid($valor) {
        $this->tbsolicitudencargosprayid = $valor; }

    public function getTbsolicitudencargoflorespintadas() {
        return $this->tbsolicitudencargoflorespintadas; }

    public function setTbsolicitudencargoflorespintadas($valor) {
        $this->tbsolicitudencargoflorespintadas = $valor; }

    public function getTbsolicitudencargopreciopintura() {
        return $this->tbsolicitudencargopreciopintura; }

    public function setTbsolicitudencargopreciopintura($valor) {
        $this->tbsolicitudencargopreciopintura = $valor; }

    public function getTbsolicitudencargocostopintura() {
        return $this->tbsolicitudencargocostopintura; }

    public function setTbsolicitudencargocostopintura($valor) {
        $this->tbsolicitudencargocostopintura = $valor; }

    public function getTbsolicitudencargoporcentaje() {
        return $this->tbsolicitudencargoporcentaje; }

    public function setTbsolicitudencargoporcentaje($valor) {
        $this->tbsolicitudencargoporcentaje = $valor; }

    public function getTbsolicitudencargocosto() {
        return $this->tbsolicitudencargocosto; }

    public function setTbsolicitudencargocosto($valor) {
        $this->tbsolicitudencargocosto = $valor; }

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
