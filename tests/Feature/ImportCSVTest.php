<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Storage;
use App\Models\ImportMeta;
use App\Jobs\ImportCSV;
use App\Models\User;
use Throwable;

class ImportCSVTest extends TestCase
{
    use RefreshDatabase;
    /**
     * Ensure import persists mismatches to database
     */
    public function test_creates_mismatches(): void
    {
        $filename = 'creates-mismatches.csv';
        $user = User::factory()->uploader()->create();
        $import = ImportMeta::factory()->for($user)->create([
            'filename' => $filename
        ]);

        $header = config('imports.upload.column_keys');
        $lines = [
            ["Q184746","Q184746$7814880A-A6EF-40EC-885E-F46DD58C8DC5","P569","3 April 1934"
            ,"Q12138","1934-04-03","https://d-nb.info/gnd/119004453","statement"],
            ["Q184746","Q184746$7200D1AD-E4E8-401B-8D57-8C823810F11F","P21","Q6581072"
            ,"","nonbinary","https://www.imdb.com/name/nm0328762/","statement"]
        ];

        $content = join("\n", array_map(function (array $line) {
            return join(',', $line);
        }, array_merge([$header], $lines)));

        Storage::fake('local');
        Storage::put(
            'mismatch-files/' . $filename,
            $content
        );

        $expected = array_map(function ($row) use ($header) {
            return array_combine($header, $row);
        }, $lines);

        ImportCSV::dispatch($import);

        $this->assertDatabaseCount('mismatches', count($lines));
        $this->assertDatabaseHas('mismatches', [
            'import_id' => $import->id
        ]);

        foreach ($expected as $mismatch) {
            $this->assertDatabaseHas('mismatches', $mismatch);
        }
    }

    /**
     * Ensure no change is commited to Database in case of error
     */
    public function test_rolls_back_on_failure(): void
    {
        $filename = 'fails-gracefully.csv';
        $user = User::factory()->uploader()->create();
        $import = ImportMeta::factory()->for($user)->create([
            'filename' => $filename
        ]);

        $header = config('imports.upload.column_keys');
        $lines = [
            ["Q184746","Q184746$7814880A-A6EF-40EC-885E-F46DD58C8DC5","P569"
            ,"3 April 1934","Q12138","1934-04-03","https://d-nb.info/gnd/119004453"],
            ["Q184746","Q184746$7200D1AD-E4E8-401B-8D57-8C823810F11F","P21"
            ,"Q6581072","","nonbinary"]
        ];

        $content = join("\n", array_map(function (array $line) {
            return join(',', $line);
        }, array_merge([$header], $lines)));

        Storage::fake('local');
        Storage::put(
            'mismatch-files/' . $filename,
            $content
        );

        try {
            ImportCSV::dispatch($import);
        } catch (Throwable $ignored) {
            $this->assertDatabaseHas('import_meta', [
                'id' => $import->id,
                'status' => 'failed'
            ]);
            $this->assertDatabaseHas('import_failures', [
                'id' => $import->id
            ]);
            $this->assertDatabaseCount('mismatches', 0);
            $this->assertDatabaseMissing('mismatches', [
                'import_id' => $import->id
            ]);
        }
    }
}
