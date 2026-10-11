<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Material;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MaterialWebCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_assigned_lecturer_can_discover_and_complete_material_crud(): void
    {
        Storage::fake('local');
        $lecturer = User::factory()->dosen()->create();
        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);
        $this->actingAs($lecturer);

        $this->get(route('dosen.courses.show', $course))
            ->assertOk()
            ->assertSee(route('dosen.courses.materials.index', $course), false)
            ->assertSee(route('dosen.courses.materials.create', $course), false);

        $this->get(route('dosen.courses.materials.index', $course))
            ->assertOk()
            ->assertSee('Tambah Materi');

        $this->get(route('dosen.courses.materials.create', $course))
            ->assertOk()
            ->assertSee(route('dosen.courses.materials.store', $course), false);

        $file = UploadedFile::fake()->createWithContent('modul-laravel.pdf', 'PDF material content');
        $this->post(route('dosen.courses.materials.store', $course), [
            'title' => 'Modul Laravel',
            'description' => 'Materi praktikum.',
            'type' => 'file',
            'file' => $file,
        ])->assertRedirect(route('dosen.courses.materials.index', $course));

        $material = Material::query()->where('title', 'Modul Laravel')->firstOrFail();
        $this->assertSame($course->id, $material->course_id);
        $this->assertSame($lecturer->id, $material->uploaded_by);
        $this->assertSame('modul-laravel.pdf', $material->original_name);
        Storage::disk('local')->assertExists($material->file_path);

        $this->get(route('dosen.materials.download', $material))
            ->assertOk()
            ->assertDownload('modul-laravel.pdf');

        $this->get(route('dosen.materials.edit', $material))
            ->assertOk()
            ->assertSee(route('dosen.materials.update', $material), false);

        $firstFilePath = $material->file_path;
        $replacement = UploadedFile::fake()->createWithContent('modul-laravel-revisi.pdf', 'Updated PDF content');
        $this->put(route('dosen.materials.update', $material), [
            'title' => 'Modul Laravel Revisi',
            'description' => 'Materi praktikum revisi.',
            'type' => 'file',
            'file' => $replacement,
        ])->assertRedirect(route('dosen.courses.materials.index', $course));

        $material->refresh();
        $this->assertSame('modul-laravel-revisi.pdf', $material->original_name);
        Storage::disk('local')->assertMissing($firstFilePath);
        Storage::disk('local')->assertExists($material->file_path);

        $replacementPath = $material->file_path;
        $this->put(route('dosen.materials.update', $material), [
            'title' => 'Dokumentasi Laravel',
            'description' => 'Tautan dokumentasi resmi.',
            'type' => 'link',
            'external_url' => 'https://laravel.com/docs',
        ])->assertRedirect(route('dosen.courses.materials.index', $course));

        $this->assertDatabaseHas('materials', [
            'id' => $material->id,
            'title' => 'Dokumentasi Laravel',
            'type' => 'link',
            'file_path' => null,
            'external_url' => 'https://laravel.com/docs',
        ]);
        Storage::disk('local')->assertMissing($replacementPath);

        $this->delete(route('dosen.materials.destroy', $material))
            ->assertRedirect(route('dosen.courses.materials.index', $course));

        $this->assertDatabaseMissing('materials', ['id' => $material->id]);
    }

    public function test_another_lecturer_cannot_manage_materials_for_the_course(): void
    {
        Storage::fake('local');
        $owner = User::factory()->dosen()->create();
        $otherLecturer = User::factory()->dosen()->create();
        $course = Course::factory()->create(['lecturer_id' => $owner->id]);
        $material = Material::factory()->create([
            'course_id' => $course->id,
            'uploaded_by' => $owner->id,
        ]);
        $this->actingAs($otherLecturer);

        $this->get(route('dosen.courses.materials.index', $course))->assertForbidden();
        $this->get(route('dosen.courses.materials.create', $course))->assertForbidden();
        $this->post(route('dosen.courses.materials.store', $course), [
            'title' => 'Tidak diizinkan',
            'description' => 'Tidak diizinkan.',
            'type' => 'link',
            'external_url' => 'https://example.test',
        ])->assertForbidden();
        $this->get(route('dosen.materials.edit', $material))->assertForbidden();
        $this->get(route('dosen.materials.download', $material))->assertForbidden();
        $this->put(route('dosen.materials.update', $material), [
            'title' => 'Tidak diizinkan',
            'description' => 'Tidak diizinkan.',
            'type' => 'link',
            'external_url' => 'https://example.test',
        ])->assertForbidden();
        $this->delete(route('dosen.materials.destroy', $material))->assertForbidden();

        $this->assertDatabaseHas('materials', [
            'id' => $material->id,
            'title' => $material->title,
        ]);
    }

    public function test_enrolled_student_can_list_course_materials_but_cannot_manage_them(): void
    {
        Storage::fake('local');
        $student = User::factory()->mahasiswa()->create();
        $course = Course::factory()->create();
        $course->students()->attach($student->id, ['enrolled_at' => now()]);
        $material = Material::factory()->create([
            'course_id' => $course->id,
            'uploaded_by' => $course->lecturer_id,
            'title' => 'Materi untuk mahasiswa',
        ]);
        $this->actingAs($student);

        $this->get(route('mahasiswa.courses.show', $course))
            ->assertOk()
            ->assertSee(route('mahasiswa.courses.materials.index', $course), false)
            ->assertDontSee('Tambah Materi');

        $this->get(route('mahasiswa.courses.materials.index', $course))
            ->assertOk()
            ->assertSee('Materi untuk mahasiswa')
            ->assertDontSee('Tambah Materi')
            ->assertDontSee('Edit');

        $this->get(route('mahasiswa.materials.show', $material))->assertOk();
        $this->get(route('dosen.courses.materials.create', $course))->assertForbidden();
    }

    public function test_admin_material_form_submits_to_admin_routes(): void
    {
        Storage::fake('local');
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create();
        $this->actingAs($admin);

        $this->get(route('admin.courses.materials.create', $course))
            ->assertOk()
            ->assertSee(route('admin.courses.materials.store', $course), false);

        $this->post(route('admin.courses.materials.store', $course), [
            'title' => 'Materi admin',
            'description' => 'Ditambahkan admin.',
            'type' => 'link',
            'external_url' => 'https://example.test/admin',
        ])->assertRedirect(route('admin.courses.materials.index', $course));

        $material = Material::query()->where('title', 'Materi admin')->firstOrFail();

        $this->get(route('admin.materials.edit', $material))
            ->assertOk()
            ->assertSee(route('admin.materials.update', $material), false);
    }

    public function test_link_material_requires_a_valid_url(): void
    {
        $lecturer = User::factory()->dosen()->create();
        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);

        $this->actingAs($lecturer)
            ->from(route('dosen.courses.materials.create', $course))
            ->post(route('dosen.courses.materials.store', $course), [
                'title' => 'Tautan tanpa URL',
                'description' => 'URL harus diisi.',
                'type' => 'link',
                'external_url' => 'bukan-url',
            ])
            ->assertSessionHasErrors('external_url');

        $this->assertDatabaseMissing('materials', ['title' => 'Tautan tanpa URL']);
    }

    public function test_file_material_requires_an_uploaded_file(): void
    {
        $lecturer = User::factory()->dosen()->create();
        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);

        $this->actingAs($lecturer)
            ->from(route('dosen.courses.materials.create', $course))
            ->post(route('dosen.courses.materials.store', $course), [
                'title' => 'File yang tidak diunggah',
                'description' => 'Unggahan wajib.',
                'type' => 'file',
            ])
            ->assertSessionHasErrors('file');

        $this->assertDatabaseMissing('materials', ['title' => 'File yang tidak diunggah']);
    }

    public function test_deleting_a_file_material_removes_its_private_upload(): void
    {
        Storage::fake('local');
        $lecturer = User::factory()->dosen()->create();
        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);
        $file = UploadedFile::fake()->createWithContent('handout.pdf', 'PDF handout');

        $this->actingAs($lecturer)
            ->post(route('dosen.courses.materials.store', $course), [
                'title' => 'Handout',
                'description' => 'Materi PDF.',
                'type' => 'file',
                'file' => $file,
            ])
            ->assertRedirect();

        $material = Material::query()->where('title', 'Handout')->firstOrFail();
        Storage::disk('local')->assertExists($material->file_path);

        $this->delete(route('dosen.materials.destroy', $material))->assertRedirect();

        Storage::disk('local')->assertMissing($material->file_path);
        $this->assertDatabaseMissing('materials', ['id' => $material->id]);
    }
}
