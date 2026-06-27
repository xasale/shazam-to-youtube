<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ResolveController extends Controller
{
    //

    public function resolve(Request $request)
    {
        $file = $request->file('csv');
        $lines = file($file->getPathname());

        // Παράλειψε τις πρώτες 2 γραμμές
        $lines = array_slice($lines, 2);

        $songs = [];

        foreach ($lines as $line) {
            $columns = str_getcsv($line, ","); // το CSV χωρίζει με κόμμα, όχι με tab

            // dd($columns); //  "dump and die" — σταματά την εκτέλεση και τυπώνει την τιμή.

            $songs[] = [
                'shazam_order' => $columns[0],
                'title'        => $columns[2],
                'artist'       => $columns[3],
            ];
        }

        return response()->json($songs);
    }
}
