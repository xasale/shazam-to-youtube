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
        set_time_limit(300);

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

        return response()->json(Song::orderBy('shazam_order')->get());
    }
}
