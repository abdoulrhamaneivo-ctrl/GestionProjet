<?php

declare(strict_types=1);

namespace App\Core;

require_once dirname(__DIR__) . '/../public/lib/fpdf.php';

final class PdfReport
{
    private \FPDF $pdf;

    public function __construct(string $studentName, string $studentEmail)
    {
        $this->pdf = new \FPDF('P', 'mm', 'A4');
        $this->pdf->SetTitle('Bilan Officiel des Evaluations - EMSP');
        $this->pdf->SetAuthor('EMSP Assignment Manager');
        $this->pdf->SetCreator('EMSP Club Informatique DSER');
        $this->pdf->AddPage();

        $this->renderHeader($studentName, $studentEmail);
    }

    private function renderHeader(string $studentName, string $studentEmail): void
    {
        $this->pdf->SetFont('Helvetica', 'B', 14);
        $this->pdf->SetTextColor(15, 23, 42);
        $this->pdf->Cell(0, 10, utf8_decode('ECOLE MULTINATIONALE SUPERIEURE DES POSTES (EMSP)'), 0, 1, 'C');

        $this->pdf->SetFont('Helvetica', 'B', 16);
        $this->pdf->Cell(0, 12, utf8_decode('Bilan Officiel des Evaluations de Projets'), 0, 1, 'C');

        $this->pdf->SetFont('Helvetica', '', 10);
        $this->pdf->Cell(0, 7, utf8_decode('Année Académique 2026 - Digitalization of Services (DSER)'), 0, 1, 'C');
        $this->pdf->Ln(3);

        $this->pdf->SetFont('Helvetica', '', 10);
        $this->pdf->Cell(95, 7, utf8_decode('Etudiant : ' . $studentName), 0, 0, 'L');
        $this->pdf->Cell(95, 7, utf8_decode('Email : ' . $studentEmail), 0, 1, 'R');
        $this->pdf->Cell(0, 7, utf8_decode('Genere le : ' . date('d/m/Y a H:i:s')), 0, 1, 'L');
        $this->pdf->Line(10, $this->pdf->GetY(), 200, $this->pdf->GetY());
        $this->pdf->Ln(4);
    }

    public function addProject(string $exerciceTitle, ?string $projectTitle, ?string $theme, ?string $groupName, ?float $design, ?float $code, ?float $fonc, ?float $total, ?string $critique): void
    {
        $this->pdf->SetFont('Helvetica', 'B', 11);
        $this->pdf->SetFillColor(241, 245, 249);
        $this->pdf->Cell(0, 8, utf8_decode('EXERCICE : ' . $exerciceTitle), 1, 1, 'L', true);

        $this->pdf->SetFont('Helvetica', '', 10);
        $this->pdf->Cell(0, 6, utf8_decode('Projet : ' . ($projectTitle ?? '-')), 0, 1, 'L');
        if ($theme !== null && $theme !== '') {
            $this->pdf->Cell(0, 6, utf8_decode('Theme : ' . $theme), 0, 1, 'L');
        }
        if ($groupName !== null && $groupName !== '') {
            $this->pdf->Cell(0, 6, utf8_decode('Groupe / Binome : ' . $groupName), 0, 1, 'L');
        }

        $this->pdf->Ln(1);
        $hasCriteria = $design !== null || $code !== null || $fonc !== null;
        if ($hasCriteria) {
            $this->pdf->SetFont('Helvetica', 'B', 10);
            $this->pdf->Cell(63, 7, utf8_decode('Design : ' . ($design !== null ? number_format($design, 2) . ' / 5.00' : '- / 5.00')), 0, 0, 'L');
            $this->pdf->Cell(63, 7, utf8_decode('Qualite Code : ' . ($code !== null ? number_format($code, 2) . ' / 5.00' : '- / 5.00')), 0, 0, 'L');
            $this->pdf->Cell(64, 7, utf8_decode('Fonctionnel : ' . ($fonc !== null ? number_format($fonc, 2) . ' / 10.00' : '- / 10.00')), 0, 1, 'L');
        }

        $this->pdf->SetFont('Helvetica', 'B', 11);
        $this->pdf->SetTextColor(220, 38, 38);
        $this->pdf->Cell(0, 8, utf8_decode('NOTE FINALE : ' . ($total !== null ? number_format($total, 2) . ' / 20.00' : '- / 20.00')), 0, 1, 'L');
        $this->pdf->SetTextColor(0, 0, 0);

        if ($critique !== null && $critique !== '') {
            $this->pdf->SetFont('Helvetica', 'I', 10);
            $this->pdf->MultiCell(0, 6, utf8_decode("Critique de l'encadrant :\n" . $critique));
        }

        $this->pdf->Ln(3);
        $this->pdf->SetDrawColor(180, 180, 180);
        $this->pdf->Line(10, $this->pdf->GetY(), 200, $this->pdf->GetY());
        $this->pdf->Ln(3);
    }

    public function output(string $filename): void
    {
        $this->pdf->Output('I', $filename);
    }
}
