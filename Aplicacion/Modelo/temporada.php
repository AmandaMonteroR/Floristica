<?php

// Temporada con precios especiales (San Valentín, Día de la Madre...). Vigente = sus precios se están usando.
class Temporada
{
    private $tbtemporadaid;
    private $tbtemporadanombre;
    private $tbtemporadadescripcion;
    private $tbtemporadavigente;
    private $tbtemporadaestado;

    public function __construct($datos = [])
    {
        $this->tbtemporadaid          = $datos['tbtemporadaid'] ?? null;
        $this->tbtemporadanombre      = $datos['tbtemporadanombre'] ?? '';
        $this->tbtemporadadescripcion = $datos['tbtemporadadescripcion'] ?? '';
        $this->tbtemporadavigente     = $datos['tbtemporadavigente'] ?? 0;
        $this->tbtemporadaestado      = $datos['tbtemporadaestado'] ?? 1;
    }

    public function getTbtemporadaid() {
        return $this->tbtemporadaid; }

    public function setTbtemporadaid($valor) {
        $this->tbtemporadaid = $valor; }

    public function getTbtemporadanombre() {
        return $this->tbtemporadanombre; }

    public function setTbtemporadanombre($valor) {
        $this->tbtemporadanombre = $valor; }

    public function getTbtemporadadescripcion() {
        return $this->tbtemporadadescripcion; }

    public function setTbtemporadadescripcion($valor) {
        $this->tbtemporadadescripcion = $valor; }

    public function getTbtemporadavigente() {
        return $this->tbtemporadavigente; }

    public function setTbtemporadavigente($valor) {
        $this->tbtemporadavigente = $valor; }

    public function getTbtemporadaestado() {
        return $this->tbtemporadaestado; }

    public function setTbtemporadaestado($valor) {
        $this->tbtemporadaestado = $valor; }

    public function estaActivo()
    {
        return (int) $this->tbtemporadaestado === 1;
    }

    public function estaVigente()
    {
        return (int) $this->tbtemporadavigente === 1;
    }
}
