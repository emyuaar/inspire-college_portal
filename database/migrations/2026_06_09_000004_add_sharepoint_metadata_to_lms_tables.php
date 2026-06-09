<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addColumns('assignment_submissions', [
            'sharepoint_item_id' => fn (Blueprint $table) => $table->string('sharepoint_item_id')->nullable()->after('file_name'),
            'sharepoint_path' => fn (Blueprint $table) => $table->string('sharepoint_path')->nullable()->after('sharepoint_item_id'),
            'sharepoint_url' => fn (Blueprint $table) => $table->text('sharepoint_url')->nullable()->after('sharepoint_path'),
            'stored_file_name' => fn (Blueprint $table) => $table->string('stored_file_name')->nullable()->after('file_name'),
            'drive_id' => fn (Blueprint $table) => $table->string('drive_id')->nullable()->after('sharepoint_url'),
            'file_size' => fn (Blueprint $table) => $table->unsignedBigInteger('file_size')->nullable()->after('drive_id'),
            'mime_type' => fn (Blueprint $table) => $table->string('mime_type')->nullable()->after('file_size'),
        ]);

        $this->addColumns('lms_lessons', [
            'sharepoint_item_id' => fn (Blueprint $table) => $table->string('sharepoint_item_id')->nullable()->after('file_path'),
            'sharepoint_path' => fn (Blueprint $table) => $table->string('sharepoint_path')->nullable()->after('sharepoint_item_id'),
            'sharepoint_url' => fn (Blueprint $table) => $table->text('sharepoint_url')->nullable()->after('sharepoint_path'),
            'drive_id' => fn (Blueprint $table) => $table->string('drive_id')->nullable()->after('sharepoint_url'),
            'file_name' => fn (Blueprint $table) => $table->string('file_name')->nullable()->after('drive_id'),
            'stored_file_name' => fn (Blueprint $table) => $table->string('stored_file_name')->nullable()->after('file_name'),
            'file_size' => fn (Blueprint $table) => $table->unsignedBigInteger('file_size')->nullable()->after('stored_file_name'),
            'mime_type' => fn (Blueprint $table) => $table->string('mime_type')->nullable()->after('file_size'),
        ]);

        $this->addColumns('assignment_files', [
            'sharepoint_item_id' => fn (Blueprint $table) => $table->string('sharepoint_item_id')->nullable()->after('file_path'),
            'sharepoint_path' => fn (Blueprint $table) => $table->string('sharepoint_path')->nullable()->after('sharepoint_item_id'),
            'sharepoint_url' => fn (Blueprint $table) => $table->text('sharepoint_url')->nullable()->after('sharepoint_path'),
            'drive_id' => fn (Blueprint $table) => $table->string('drive_id')->nullable()->after('sharepoint_url'),
            'file_size' => fn (Blueprint $table) => $table->unsignedBigInteger('file_size')->nullable()->after('drive_id'),
            'mime_type' => fn (Blueprint $table) => $table->string('mime_type')->nullable()->after('file_size'),
        ]);
    }

    public function down(): void
    {
        $this->dropColumns('assignment_files', ['mime_type', 'file_size', 'drive_id', 'sharepoint_url', 'sharepoint_path', 'sharepoint_item_id']);
        $this->dropColumns('lms_lessons', ['mime_type', 'file_size', 'stored_file_name', 'file_name', 'drive_id', 'sharepoint_url', 'sharepoint_path', 'sharepoint_item_id']);
        $this->dropColumns('assignment_submissions', ['mime_type', 'file_size', 'drive_id', 'stored_file_name']);
    }

    private function addColumns(string $tableName, array $columns): void
    {
        if (!Schema::connection('mysql_portal')->hasTable($tableName)) {
            return;
        }

        Schema::connection('mysql_portal')->table($tableName, function (Blueprint $table) use ($tableName, $columns) {
            foreach ($columns as $column => $definition) {
                if (!Schema::connection('mysql_portal')->hasColumn($tableName, $column)) {
                    $definition($table);
                }
            }
        });
    }

    private function dropColumns(string $tableName, array $columns): void
    {
        if (!Schema::connection('mysql_portal')->hasTable($tableName)) {
            return;
        }

        $existing = array_values(array_filter($columns, fn ($column) => Schema::connection('mysql_portal')->hasColumn($tableName, $column)));

        if (!$existing) {
            return;
        }

        Schema::connection('mysql_portal')->table($tableName, function (Blueprint $table) use ($existing) {
            $table->dropColumn($existing);
        });
    }
};
