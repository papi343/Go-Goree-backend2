<?php

namespace App\Services\Rapports;

use App\Models\Rapport;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RapportService
{
    /**
     * List all reports with pagination.
     */
    public function listRapports(): LengthAwarePaginator
    {
        return Rapport::orderByDesc('created_at')->paginate(20);
    }

    /**
     * Generate a new report file and save it to storage.
     */
    public function generateRapport(string $type, string $mois, string $format): Rapport
    {
        $user = auth()->user();
        $generePar = $user ? $user->prenom . ' ' . $user->nom : 'Système';

        $format = strtolower($format);
        if ($format !== 'pdf' && $format !== 'xlsx' && $format !== 'csv') {
            $format = 'pdf'; // Default fallback
        }

        // Clean filename
        $slugType = Str::slug($type, '_');
        $slugMois = Str::slug($mois, '_');
        $filename = "rapport_{$slugType}_{$slugMois}." . $format;
        $storagePath = "rapports/{$filename}";

        // Write basic report content
        $content = "Rapport: " . strtoupper($type) . "\n";
        $content .= "Période: " . $mois . "\n";
        $content .= "Généré par: " . $generePar . "\n";
        $content .= "Date de génération: " . now()->toDateTimeString() . "\n";
        $content .= "----------------------------------------\n";
        $content .= "Données de synthèse de la traversée Dakar-Gorée\n";

        Storage::disk('public')->put($storagePath, $content);

        return Rapport::create([
            'nom_fichier' => $filename,
            'format' => strtoupper($format),
            'date_generation' => now(),
            'genere_par' => $generePar,
            'chemin_stockage' => $storagePath,
        ]);
    }
}
