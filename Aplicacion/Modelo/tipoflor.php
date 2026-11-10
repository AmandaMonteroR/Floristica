<?php

// Tipo de flor (Rosa, Girasol...) o de follaje (Eucalipto, Helecho...) del catálogo
class Tipoflor
{
    private $tbtipoflorid;
    private $tbtipoflornombre;
    private $tbtipoflorclase;
    private $tbtipoflorestado;

    public function __construct($datos = [])
    {
        $this->tbtipoflorid     = $datos['tbtipoflorid'] ?? null;
        $this->tbtipoflornombre = $datos['tbtipoflornombre'] ?? '';
        $this->tbtipoflorclase  = $datos['tbtipoflorclase'] ?? 'Flor';
        $this->tbtipoflorestado = $datos['tbtipoflorestado'] ?? 1;
    }

    public function getTbtipoflorid() {
        return $this->tbtipoflorid; }

    public function setTbtipoflorid($valor) {
        $this->tbtipoflorid = $valor; }

    public function getTbtipoflornombre() {
        return $this->tbtipoflornombre; }

    public function setTbtipoflornombre($valor) {
        $this->tbtipoflornombre = $valor; }

    public function getTbtipoflorclase() {
        return $this->tbtipoflorclase; }

    public function setTbtipoflorclase($valor) {
        $this->tbtipoflorclase = $valor; }

    public function getTbtipoflorestado() {
        return $this->tbtipoflorestado; }

    public function setTbtipoflorestado($valor) {
        $this->tbtipoflorestado = $valor; }
}
