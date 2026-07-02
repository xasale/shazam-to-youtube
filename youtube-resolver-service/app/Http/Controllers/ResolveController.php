<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Song;
use Illuminate\Support\Facades\Http;

class ResolveController extends Controller
{
    //

    public function resolve(Request $request)
    {
        // χωρίς χρονικό όριο καθώς χρειάζεται χρόνο για όλα τα searches
        set_time_limit(0);

        $file = $request->file('csv');
        $lines = file($file->getPathname());

        // Παράλειψε τις πρώτες 2 γραμμές
        $lines = array_slice($lines, 2);

        Song::query()->update(['shazam_order' => null]); // μηδενίζουμε το shazam_order όλων των τραγουδιών

        foreach ($lines as $line) {
            $columns = str_getcsv($line, ","); // το CSV χωρίζει με κόμμα, όχι με tab

            // dd($columns); //  "dump and die" — σταματά την εκτέλεση και τυπώνει την τιμή.

            $song = Song::firstOrNew(
                ['artist' => $columns[3], 'title' => $columns[2]]
            );
            if (is_null($song->shazam_order) || $columns[0] < $song->shazam_order) {
                $song->shazam_order = $columns[0];
            }
            if (!$song->exists) {
                $song->status = 'pending';
            }
            $song->save();
        }

        $pending = Song::where('status', 'pending')
            ->orderBy('shazam_order')
            ->get();

        // αρχικοποίηση μεταβλητής για quota check
        // το free Youtube Api επιτρέπει μόνο 100 searches/μέρα

        $searchCount = 0;

        foreach ($pending as $song) {

            // quota check
            if ($searchCount >= 95) {
                break;
            }
            $searchCount++;

            $response = Http::withOptions([
                'verify' => base_path('certs/cacert.pem'),
            ])->get('https://www.googleapis.com/youtube/v3/search', [
                'part'       => 'snippet',
                'q'          => $song->artist . ' ' . $song->title,
                'type'       => 'video',
                'maxResults' => 1,
                'key'        => env('YOUTUBE_API_KEY'),
            ]);

            // εδώ θα επεξεργαστούμε την απάντηση του Youtube
            $data = $response->json();

            if (!empty($data['items'])) {
                $videoId = $data['items'][0]['id']['videoId'];
                $song->youtube_link = 'https://www.youtube.com/watch?v=' . $videoId;
                $song->status = 'resolved';
                $song->resolved_at = now();
            } else {
                $song->status = 'failed';
            }

            $song->save();
        }

        return response()->json(Song::orderBy('shazam_order')->get());
    }
}
