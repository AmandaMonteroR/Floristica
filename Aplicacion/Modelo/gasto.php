<?php

// Gasto del negocio que no es mercadería (alquiler, luz, salarios...). Estado: 1 cuenta, 0 anulado.
class Gasto
{
    private $tbgastoid;
    private $tbgastocategoria;
    private $tbgastodescripcion;
    private $tbgastomonto;
    private $tbgastofecha;
    private $tbempleadoid;
    private $tbgastoestado;

    public function __construct($datos = [])
    {
        $this->tbgastoid          = $datos['tbgastoid'] ?? null;
        $this->tbgastocategoria   = $datos['tbgastocategoria'] ?? '';
        $this->tbgastodescripcion = $datos['tbgastodescripcion'] ?? '';
        $this->tbgastomonto       = $datos['tbgastomonto'] ?? 0;
        $this->tbgastofecha       = $datos['tbgastofecha'] ?? '';
        $this->tbempleadoid       = $datos['tbempleadoid'] ?? 0;
        $this->tbgastoestado      = $datos['tbgastoestado'] ?? 1;
    }

    public function getTbgastoid() {
        return $this->tbgastoid; }

    public function setTbgastoid($valor) {
        $this->tbgastoid = $valor; }

    public function getTbgastocategoria() {
        return $this->tbgastocategoria; }

    public function setTbgastocategoria($valor) {
        $this->tbgastocategoria = $valor; }

    public function getTbgastodescripcion() {
        return $this->tbgastodescripcion; }

    public function setTbgastodescripcion($valor) {
        $this->tbgastodescripcion = $valor; }

    public function getTbgastomonto() {
        return $this->tbgastomonto; }

    public function setTbgastomonto($valor) {
        $this->tbgastomonto = $valor; }

    public function getTbgastofecha() {
        return $this->tbgastofecha; }

    public function setTbgastofecha($valor) {
        $this->tbgastofecha = $valor; }

    public function getTbempleadoid() {
        return $this->tbempleadoid; }

    public function setTbempleadoid($valor) {
        $this->tbempleadoid = $valor; }

    public function getTbgastoestado() {
        return $this->tbgastoestado; }

    public function setTbgastoestado($valor) {
        $this->tbgastoestado = $valor; }

    public function estaActivo() {
        return (int) $this->tbgastoestado === 1; }
}
