<?php

// Persona que hace las solicitudes de encargo (normalmente por WhatsApp)
class Cliente
{
    private $tbclienteid;
    private $tbclientenombre;
    private $tbclientetelefono;
    private $tbclientecorreo;
    private $tbclientedireccion;
    private $tbclienteestado;
    private $tbclientefecharegistro;

    public function __construct($datos = [])
    {
        $this->tbclienteid            = $datos['tbclienteid'] ?? null;
        $this->tbclientenombre        = $datos['tbclientenombre'] ?? '';
        $this->tbclientetelefono      = $datos['tbclientetelefono'] ?? '';
        $this->tbclientecorreo        = $datos['tbclientecorreo'] ?? '';
        $this->tbclientedireccion     = $datos['tbclientedireccion'] ?? '';
        $this->tbclienteestado        = $datos['tbclienteestado'] ?? 1;
        $this->tbclientefecharegistro = $datos['tbclientefecharegistro'] ?? null;
    }

    public function getTbclienteid() {
        return $this->tbclienteid; }

    public function setTbclienteid($valor) {
        $this->tbclienteid = $valor; }

    public function getTbclientenombre() {
        return $this->tbclientenombre; }

    public function setTbclientenombre($valor) {
        $this->tbclientenombre = $valor; }

    public function getTbclientetelefono() {
        return $this->tbclientetelefono; }

    public function setTbclientetelefono($valor) {
        $this->tbclientetelefono = $valor; }

    public function getTbclientecorreo() {
        return $this->tbclientecorreo; }

    public function setTbclientecorreo($valor) {
        $this->tbclientecorreo = $valor; }

    public function getTbclientedireccion() {
        return $this->tbclientedireccion; }

    public function setTbclientedireccion($valor) {
        $this->tbclientedireccion = $valor; }

    public function getTbclienteestado() {
        return $this->tbclienteestado; }

    public function setTbclienteestado($valor) {
        $this->tbclienteestado = $valor; }

    public function getTbclientefecharegistro() {
        return $this->tbclientefecharegistro; }

    public function setTbclientefecharegistro($valor) {
        $this->tbclientefecharegistro = $valor; }

    public function estaActivo()
    {
        return (int) $this->tbclienteestado === 1;
    }
}
