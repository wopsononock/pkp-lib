<?php

/**
 * @file classes/migration/install/RolesAndUserGroupsMigration.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class RolesAndUserGroupsMigration
 * @brief Describe database table structures.
 */

namespace PKP\migration\install;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RolesAndUserGroupsMigration extends \PKP\migration\Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // User groups for a context.
        Schema::create('user_groups', function (Blueprint $table) {
            $table->bigInteger('user_group_id')->autoIncrement();
            $table->bigInteger('context_id');
            $table->bigInteger('role_id');
            $table->smallInteger('is_default')->default(0);
            $table->smallInteger('show_title')->default(1);
            $table->smallInteger('permit_self_registration')->default(0);
            $table->smallInteger('permit_metadata_edit')->default(0);
            $table->index(['user_group_id'], 'user_groups_user_group_id');
            $table->index(['context_id'], 'user_groups_context_id');
            $table->index(['role_id'], 'user_groups_role_id');
        });

        // User Group-specific settings
        Schema::create('user_group_settings', function (Blueprint $table) {
            $table->bigInteger('user_group_id');
            $table->string('locale', 14)->default('');
            $table->string('setting_name', 255);
            $table->mediumText('setting_value')->nullable();

            $table->unique(['user_group_id', 'locale', 'setting_name'], 'user_group_settings_pkey');
            $table->foreign('user_group_id')->references('user_group_id')->on('user_groups')->onDelete('cascade');
            $table->index(['user_group_id'], 'user_group_settings_user_group_id');
        });

        // User group assignments (mapping of user to user groups)
        Schema::create('user_user_groups', function (Blueprint $table) {
            $table->bigInteger('user_group_id');
            $table->foreign('user_group_id')->references('user_group_id')->on('user_groups')->onDelete('cascade');
            $table->index(['user_group_id'], 'user_user_groups_user_group_id');

            $table->bigInteger('user_id');
            $table->foreign('user_id', 'user_user_groups_user_id')->references('user_id')->on('users')->onDelete('cascade');
            $table->index(['user_id'], 'user_user_groups_user_id');

            $table->unique(['user_group_id', 'user_id'], 'user_user_groups_pkey');
        });

        // User groups assignments to stages in the workflow
        Schema::create('user_group_stage', function (Blueprint $table) {
            $table->bigInteger('context_id');
            $contextDao = \APP\core\Application::getContextDAO();
            $table->foreign('context_id', 'user_group_stage_context_id')->references($contextDao->primaryKeyColumn)->on($contextDao->tableName)->onDelete('cascade');
            $table->index(['context_id'], 'user_group_stage_context_id');

            $table->bigInteger('user_group_id');
            $table->foreign('user_group_id', 'user_group_stage_user_group_id')->references('user_group_id')->on('user_groups')->onDelete('cascade');
            $table->index(['user_group_id'], 'user_group_stage_user_group_id');

            $table->bigInteger('stage_id');
            $table->index(['stage_id'], 'user_group_stage_stage_id');

            $table->unique(['context_id', 'user_group_id', 'stage_id'], 'user_group_stage_pkey');
        });

        // Stage Assignments
        Schema::create('stage_assignments', function (Blueprint $table) {
            $table->bigInteger('stage_assignment_id')->autoIncrement();

            // The foreign key for this column is declared with the submissions table.
            $table->bigInteger('submission_id');

            $table->bigInteger('user_group_id');
            $table->foreign('user_group_id', 'stage_assignments_user_group_id')->references('user_group_id')->on('user_groups')->onDelete('cascade');
            $table->index(['user_group_id'], 'stage_assignments_user_group_id');

            $table->bigInteger('user_id');
            $table->foreign('user_id', 'stage_assignments_user_id')->references('user_id')->on('users')->onDelete('cascade');
            $table->index(['user_id'], 'stage_assignments_user_id');

            $table->datetime('date_assigned');
            $table->smallInteger('recommend_only')->default(0);
            $table->smallInteger('can_change_metadata')->default(0);

            $table->unique(['submission_id', 'user_group_id', 'user_id'], 'stage_assignment');
        });
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Schema::drop('stage_assignments');
        Schema::drop('user_group_stage');
        Schema::drop('user_user_groups');
        Schema::drop('user_group_settings');
        Schema::drop('user_groups');
    }
}
