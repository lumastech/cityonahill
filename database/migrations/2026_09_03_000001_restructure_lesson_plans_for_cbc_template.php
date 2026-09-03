<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Reshapes lesson plans around the school's CBC lesson plan form: a header block
 * (competences, goal, reference, prior knowledge, environment), a class block
 * (date, duration, pupil counts) and a stage table (introduction / development /
 * application) held as JSON.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lesson_plans', function (Blueprint $table) {
            $table->renameColumn('title', 'topic');
            $table->renameColumn('objectives', 'lesson_goal');
            $table->renameColumn('materials', 'learning_material');
        });

        Schema::table('lesson_plans', function (Blueprint $table) {
            $table->string('sub_topic')->nullable()->after('topic');
            $table->text('general_competence')->nullable()->after('sub_topic');
            $table->text('specific_competence')->nullable()->after('general_competence');
            $table->text('reference')->nullable()->after('lesson_goal');
            $table->text('prior_knowledge')->nullable()->after('reference');
            $table->text('learning_environment')->nullable()->after('learning_material');
            $table->json('stages')->nullable()->after('learning_environment');
            $table->text('conclusion')->nullable()->after('stages');
            $table->text('evaluation')->nullable()->after('conclusion');
            $table->unsignedSmallInteger('duration_minutes')->nullable()->after('lesson_date');
            $table->unsignedSmallInteger('boys_count')->nullable()->after('duration_minutes');
            $table->unsignedSmallInteger('girls_count')->nullable()->after('boys_count');
        });

        // Carry the old free-text content/activities into the development stage so
        // nothing written before the redesign is lost.
        DB::table('lesson_plans')->orderBy('id')->each(function ($plan) {
            DB::table('lesson_plans')->where('id', $plan->id)->update([
                'stages' => json_encode([
                    ['stage' => 'Introduction', 'teacher_activity' => null, 'learner_activity' => null, 'assessment_criteria' => null],
                    ['stage' => 'Development', 'teacher_activity' => $plan->content, 'learner_activity' => $plan->activities, 'assessment_criteria' => null],
                    ['stage' => 'Application', 'teacher_activity' => null, 'learner_activity' => null, 'assessment_criteria' => null],
                ]),
            ]);
        });

        Schema::table('lesson_plans', function (Blueprint $table) {
            $table->dropColumn(['content', 'activities']);
        });
    }

    public function down(): void
    {
        Schema::table('lesson_plans', function (Blueprint $table) {
            $table->text('content')->nullable()->after('lesson_goal');
            $table->text('activities')->nullable()->after('content');
        });

        DB::table('lesson_plans')->orderBy('id')->each(function ($plan) {
            $stages = json_decode((string) $plan->stages, true) ?: [];
            $development = collect($stages)->firstWhere('stage', 'Development') ?? [];

            DB::table('lesson_plans')->where('id', $plan->id)->update([
                'content' => $development['teacher_activity'] ?? '',
                'activities' => $development['learner_activity'] ?? null,
            ]);
        });

        Schema::table('lesson_plans', function (Blueprint $table) {
            $table->dropColumn([
                'sub_topic', 'general_competence', 'specific_competence', 'reference',
                'prior_knowledge', 'learning_environment', 'stages',
                'conclusion', 'evaluation',
                'duration_minutes', 'boys_count', 'girls_count',
            ]);
        });

        Schema::table('lesson_plans', function (Blueprint $table) {
            $table->renameColumn('topic', 'title');
            $table->renameColumn('lesson_goal', 'objectives');
            $table->renameColumn('learning_material', 'materials');
        });
    }
};
