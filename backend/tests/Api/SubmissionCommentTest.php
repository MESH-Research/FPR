<?php
declare(strict_types=1);

namespace Tests\Api;

use App\Models\InlineComment;
use App\Models\OverallComment;
use App\Models\Submission;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Str;
use Tests\ApiTestCase;
use Tests\TestFactory;

class SubmissionCommentTest extends ApiTestCase
{
    use WithFaker;
    use RefreshDatabase;
    use TestFactory;

    public function testRetrieveInlineComments()
    {
        $this->beAppAdmin();

        $submission = $this->createSubmissionWithInlineComment(2);
        $response = $this->graphQL(
            'query GetSubmission($id: ID!) {
                submission (id: $id) {
                    id
                    inline_comments {
                        created_by {
                            name
                        }
                        updated_by {
                            name
                        }
                        content
                        style_criteria {
                            name
                            icon
                        }
                    }
                }
            }',
            ['id' => $submission->id]
        );
        $expected_data = [
            'submission' => [
                'id' => (string)$submission->id,
                'inline_comments' => [
                    '0' => [
                        'created_by' => [
                            'name' => $submission->inlineComments->first()->createdBy->name,
                        ],
                        'updated_by' => [
                            'name' => $submission->inlineComments->first()->updatedBy->name,
                        ],
                        'content' => 'This is some content for an inline comment created by PHPUnit.',
                        'style_criteria' => [
                            '0' => [
                                'name' => 'PHPUnit Criteria',
                                'icon' => 'php',
                            ],
                        ],
                    ],
                    '1' => [
                        'created_by' => [
                            'name' => $submission->inlineComments->last()->createdBy->name,
                        ],
                        'updated_by' => [
                            'name' => $submission->inlineComments->last()->updatedBy->name,
                        ],
                        'content' => 'This is some content for an inline comment created by PHPUnit.',
                        'style_criteria' => [
                            '0' => [
                                'name' => 'PHPUnit Criteria',
                                'icon' => 'php',
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $response->assertJsonPath('data', $expected_data);
    }

    public function testRetrieveOverallComments()
    {
        $this->beAppAdmin();

        $submission = $this->createSubmissionWithOverallComment(2);
        $response = $this->graphQL(
            'query GetSubmission($id: ID!) {
                submission (id: $id) {
                    id
                    overall_comments {
                        created_by {
                            name
                        }
                        updated_by {
                            name
                        }
                        content
                    }
                }
            }',
            ['id' => $submission->id]
        );
        $expected_data = [
            'submission' => [
                'id' => (string)$submission->id,
                'overall_comments' => [
                    '0' => [
                        'created_by' => [
                            'name' => $submission->overallComments->first()->createdBy->name,
                        ],
                        'updated_by' => [
                            'name' => $submission->overallComments->first()->updatedBy->name,
                        ],
                        'content' => 'This is some content for an overall comment created by PHPUnit.',
                    ],
                    '1' => [
                        'created_by' => [
                            'name' => $submission->overallComments->last()->createdBy->name,
                        ],
                        'updated_by' => [
                            'name' => $submission->overallComments->last()->updatedBy->name,
                        ],
                        'content' => 'This is some content for an overall comment created by PHPUnit.',
                    ],
                ],
            ],
        ];
        $response->assertJsonPath('data', $expected_data);
    }

    /**
     * @return array
     */
    public static function commentCreationProvider(): array
    {
        return [
            'parent_id missing and reply_to_id missing' => [true, ''],
            'parent_id null and reply_to_id null' => [true, 'parent_id: null, reply_to_id: null'],
        ];
    }

    /**
     * @dataProvider commentCreationProvider
     * @param bool $is_valid Expected validity of test case
     * @param string $fragment GraphQL fragment to include in the mutation
     * @return void
     */
    public function testCreateInlineComment(bool $is_valid, string $fragment)
    {
        $this->beAppAdmin();
        $submission = $this->createSubmission();
        $criteria_1 = $this->createStyleCriteria($submission->publication->id);
        $criteria_2 = $this->createStyleCriteria($submission->publication->id);
        $response = $this->graphQL(
            'mutation AddInlineComment($submission_id: ID!) {
                updateSubmission(input: {
                    id: $submission_id
                    inline_comments: {
                        create: [{
                            content: "Test Content"
                            style_criteria: [' . $criteria_1->id . ', ' . $criteria_2->id . ']
                            ' . $fragment . '
                            from: 100
                            to: 110
                        }]
                    }
                }) {
                    inline_comments {
                        content
                        style_criteria {
                            name
                            icon
                        }
                        read_at
                        from
                        to
                    }
                }
            }',
            [
                'submission_id' => $submission->id,
            ]
        );
        $expected = [
            'updateSubmission' => [
                'inline_comments' => [
                    [
                        'content' => 'Test Content',
                        'style_criteria' => [
                            '0' => [
                                'name' => $criteria_1->name,
                                'icon' => $criteria_1->icon,
                            ],
                            '1' => [
                                'name' => $criteria_2->name,
                                'icon' => $criteria_2->icon,
                            ],
                        ],
                        'read_at' => null,
                        'from' => 100,
                        'to' => 110,
                    ],
                ],
            ],
        ];
        if ($is_valid) {
            $response->assertJsonPath('data', $expected);
        } else {
            $this->assertStringStartsWith('Validation failed', $response->json('errors.0.message'));
        }
    }

    /**
     * @return array
     */
    public static function commentReplyCreationProvider(): array
    {
        return [
            'parent_id with a value and reply_to_id with a value' =>
            [true, ['parent_id' => true, 'reply_to_id' => true]],
            'parent_id with a value and reply_to_id missing' =>
            [false, ['parent_id' => true]],
            'parent_id null and reply_to_id missing' =>
            [false, ['parent_id' => null]],
            'parent_id null and reply_to_id with a value' =>
            [false, ['parent_id' => null, 'reply_to_id' => true]],
            'parent_id with a value and reply_to_id null' =>
            [false, ['parent_id' => true, 'reply_to_id' => null]],
            'parent_id missing and reply_to_id null' =>
            [false, ['reply_to_id' => null]],
            'parent_id missing and reply_to_id with a value' =>
            [false, ['reply_to_id' => true]],
        ];
    }

    /**
     * @dataProvider commentReplyCreationProvider
     * @param bool $is_valid Expected validity of test case
     * @param array $args Arguments to include in the GraphQL mutation
     * @return void
     */
    public function testCreateInlineCommentReply(bool $is_valid, array $args): void
    {
        $this->beAppAdmin();
        $submission = $this->createSubmissionWithInlineComment();
        $inline_comment = $submission->inlineComments()->first();

        //phpcs:disable
        $parentId = $inline_comment->id;
        $replyToId = $inline_comment->id;
        //phpcs:enable

        $arguments = [];
        $fragment = [];
        $variables = ['submissionId' => $submission->id];
        foreach ($args as $input => $variable) {
            $camelName = Str::camel($input);
            $arguments[] = "$$camelName: ID";
            $fragment[] = "$input: $$camelName";
            $variables[$camelName] = $variable ? $$camelName : null;
        }

        $graphQL =
            /** @lang GraphQL */
            'mutation CreateInlineCommentReply($submissionId: ID! ' . implode(' ', $arguments) . ') {
                updateSubmission(input: {
                    id: $submissionId
                    inline_comments: {
                        create: [
                            {
                                content: "New Inline Comment Reply"
                                ' . implode("\n", $fragment) . '
                            }
                        ]
                    }
                }) {
                    inline_comments {
                        replies {
                            content
                            parent_id
                            reply_to_id
                        }
                    }
                }
            }';

        $response = $this->graphql(
            $graphQL,
            $variables
        );

        if ($is_valid) {
            $response->assertJsonPath('data.updateSubmission.inline_comments', [
                [
                    'replies' => [
                        [
                            'content' => 'New Inline Comment Reply',
                            'parent_id' => (string)$inline_comment->id,
                            'reply_to_id' => (string)$inline_comment->id,
                        ],
                    ],
                ],
            ]);
        } else {
            $this->assertStringStartsWith('Validation failed', $response->json('errors.0.message'));
        }
    }

    /**
     * @dataProvider commentCreationProvider
     * @param bool $is_valid Expected validity of test case
     * @param string $fragment GraphQL fragment to include in the mutation
     * @return void
     */
    public function testCreateOverallComment(bool $is_valid, string $fragment): void
    {
        $this->beAppAdmin();
        $submission = $this->createSubmission();

        $response = $this->graphQL(
            /** @lang GraphQL */
            'mutation CreateOverallComment($submissionId: ID!) {
                updateSubmission(input: {
                    id: $submissionId
                    overall_comments: {
                        create: [
                            {
                                content: "New Overall Comment"
                                ' . $fragment . '
                            }
                        ]
                    }
                }) {
                    overall_comments {
                        content
                        read_at
                    }
                }
            }',
            [
                'submissionId' => $submission->id,
            ]
        );
        if ($is_valid) {
            $response->assertJsonPath('data.updateSubmission.overall_comments', [
                [
                    'content' => 'New Overall Comment',
                    'read_at' => null,
                ],
            ]);
        } else {
            $this->assertStringStartsWith('Validation failed', $response->json('errors.0.message'));
        }
    }

    /**
     * @dataProvider commentReplyCreationProvider
     * @param bool $is_valid Expected validity of test case
     * @param array $args Arguments to include in the GraphQL mutation
     * @return void
     */
    public function testCreateOverallCommentReply(bool $is_valid, array $args): void
    {
        $this->beAppAdmin();
        $submission = $this->createSubmissionWithOverallComment();
        $overall_comment = $submission->overallComments()->first();

        //phpcs:disable
        $parentId = $overall_comment->id;
        $replyToId = $overall_comment->id;
        //phpcs:enable

        $arguments = [];
        $fragment = [];
        $variables = ['submissionId' => $submission->id];
        foreach ($args as $input => $variable) {
            $camelName = Str::camel($input);
            $arguments[] = "$$camelName: ID";
            $fragment[] = "$input: $$camelName";
            $variables[$camelName] = $variable ? $$camelName : null;
        }

        $graphQL =
            /** @lang GraphQL */
            'mutation CreateOverallCommentReply($submissionId: ID! ' . implode(' ', $arguments) . ') {
                updateSubmission(input: {
                    id: $submissionId
                    overall_comments: {
                        create: [
                            {
                                content: "New Overall Comment Reply"
                                ' . implode("\n", $fragment) . '
                            }
                        ]
                    }
                }) {
                    overall_comments {
                        replies {
                            content
                            parent_id
                            reply_to_id
                        }
                    }
                }
            }';

        $response = $this->graphql(
            $graphQL,
            $variables
        );

        if ($is_valid) {
            $response->assertJsonPath('data.updateSubmission.overall_comments', [
                [
                    'replies' => [
                        [
                            'content' => 'New Overall Comment Reply',
                            'parent_id' => (string)$overall_comment->id,
                            'reply_to_id' => (string)$overall_comment->id,
                        ],
                    ],
                ],
            ]);
        } else {
            $this->assertStringStartsWith('Validation failed', $response->json('errors.0.message'));
        }
    }

    /**
     * @return void
     */
    public function testUpdateInlineComment()
    {
        $admin = $this->beAppAdmin();
        $submission = $this->createSubmissionWithInlineComment(1, $admin);
        $inline_comment = $submission->inlineComments->first();
        $criteria_1 = $this->createStyleCriteria($submission->publication->id);
        $criteria_2 = $this->createStyleCriteria($submission->publication->id);
        $response = $this->graphQL(
            'mutation UpdateInlineComment($submissionId: ID! $commentId: ID!) {
                updateSubmission(input: {
                    id: $submissionId
                    inline_comments: {
                        update: [
                            {
                                id: $commentId
                                content: "Test Content"
                                style_criteria: [' . $criteria_1->id . ', ' . $criteria_2->id . ']
                                from: 120
                                to: 130
                            }
                        ]
                    }
                }) {
                    id
                    inline_comments {
                        id
                        content
                        style_criteria {
                            name
                            icon
                        }
                        from
                        to
                    }
                }
            }',
            [
                'commentId' => $inline_comment->id,
                'submissionId' => $submission->id,
            ]
        );
        $expected = [
            'updateSubmission' => [
                'id' => (string)$submission->id,
                'inline_comments' => [
                    [
                        'id' => (string)$inline_comment->id,
                        'content' => 'Test Content',
                        'style_criteria' => [
                            '0' => [
                                'name' => $criteria_1->name,
                                'icon' => $criteria_1->icon,
                            ],
                            '1' => [
                                'name' => $criteria_2->name,
                                'icon' => $criteria_2->icon,
                            ],
                        ],
                        'from' => 120,
                        'to' => 130,
                    ],
                ],
            ],
        ];
        $response->assertJsonPath('data', $expected);
    }

    /**
     * @return void
     */
    public function testSubmissionOutOfReviewRejectsNewInlineComments(): void
    {
        $user = $this->beSubmitter();
        $submission = $user->submissions->first();
        $this->graphQL(
            'mutation AddInlineComment ($submission_id: ID!) {
                updateSubmission(
                    input: {
                        id: $submission_id,
                        inline_comments: {create: [{content:"Test Content", reply_to_id: null, parent_id: null, from: 120, to: 130 }]}
                    }
                ) {
                    id
                }
            }',
            [
                'submission_id' => $submission->id,
            ]
        )
            ->assertGraphQLErrorMessage('Validation failed for the field [updateSubmission].')
            ->assertGraphQLValidationError(
                'input.inline_comments.create.0',
                'The submission is not in a reviewable state.'
            );
    }

    /**
     * @return void
     */
    public function testSubmissionUnderReviewAcceptsNewInlineComments(): void
    {
        $user = $this->beSubmitter();
        $submission = $user->submissions->first();
        $submission->status = Submission::UNDER_REVIEW;
        $submission->save();
        $response = $this->graphQL(
            'mutation AddInlineComment ($submission_id: ID!) {
                updateSubmission(
                    input: {
                        id: $submission_id,
                        inline_comments: {create: [{content:"Test Content", reply_to_id: null, parent_id: null, from: 120, to: 130 }]}
                    }
                ) {
                    id
                    inline_comments {
                        content
                        from
                        to
                    }
                }
            }',
            [
                'submission_id' => $submission->id,
            ]
        );
        $expected = [
            'updateSubmission' => [
                'id' => (string)$submission->id,
                'inline_comments' => [
                    [
                        'content' => 'Test Content',
                        'from' => 120,
                        'to' => 130,
                    ],
                ],
            ],
        ];
        $response->assertJsonPath('data', $expected);
    }

    /**
     * @return void
     */
    public function testSubmissionOutOfReviewRejectsNewOverallComments(): void
    {
        $user = $this->beSubmitter();
        $submission = $user->submissions->first();
        $this->graphQL(
            'mutation AddOverallComment ($submission_id: ID!) {
                updateSubmission(
                    input: {
                        id: $submission_id,
                        overall_comments: {create: [{content:"Test Content", reply_to_id: null, parent_id: null }]}
                    }
                ) {
                    id
                }
            }',
            [
                'submission_id' => $submission->id,
            ]
        )
            ->assertGraphQLErrorMessage('Validation failed for the field [updateSubmission].')
            ->assertGraphQLValidationError(
                'input.overall_comments.create.0',
                'The submission is not in a reviewable state.'
            );
    }

    /**
     * @return void
     */
    public function testSubmissionUnderReviewAcceptsNewOverallComments(): void
    {
        $user = $this->beSubmitter();
        $submission = $user->submissions->first();
        $submission->status = Submission::UNDER_REVIEW;
        $submission->save();
        $response = $this->graphQL(
            'mutation AddOverallComment ($submission_id: ID!) {
                updateSubmission(
                    input: {
                        id: $submission_id,
                        overall_comments: {create: [{content:"Test Content", reply_to_id: null, parent_id: null }]}
                    }
                ) {
                    id
                    overall_comments {
                        content
                    }
                }
            }',
            [
                'submission_id' => $submission->id,
            ]
        );
        $expected = [
            'updateSubmission' => [
                'id' => (string)$submission->id,
                'overall_comments' => [
                    [
                        'content' => 'Test Content',
                    ],
                ],
            ],
        ];
        $response->assertJsonPath('data', $expected);
    }

    /**
     * @return void
     */
    public function testUsersCannotModifyTheInlineCommentsOfOthers(): void
    {
        $this->beAppAdmin();
        $submission = $this->createSubmissionWithInlineComment();
        $inline_comment = $submission->inlineComments->first();
        $this->graphQL(
            'mutation UpdateOthersInlineComment ($submission_id: ID! $comment_id: ID!) {
                updateSubmission(
                    input: {
                        id: $submission_id,
                        inline_comments: {
                            update: {
                                id: $comment_id
                                content: "This update should not work."
                            }
                        }
                    }
                ) {
                    id
                }
            }',
            [
                'submission_id' => $submission->id,
                'comment_id' => $inline_comment->id,
            ]
        )
            ->assertGraphQLErrorMessage('UNAUTHORIZED');
    }

    /**
     * @return void
     */
    public function testUsersCannotModifyTheOverallCommentsOfOthers()
    {
        $this->beAppAdmin();
        $submission = $this->createSubmissionWithOverallComment();
        $overall_comment = $submission->overallComments->first();
        $this->graphQL(
            'mutation UpdateOthersOverallComment ($submission_id: ID! $comment_id: ID!) {
                updateSubmission(
                    input: {
                        id: $submission_id,
                        overall_comments: {
                            update: {
                                id: $comment_id
                                content: "This update should not work."
                            }
                        }
                    }
                ) {
                    id
                }
            }',
            [
                'submission_id' => $submission->id,
                'comment_id' => $overall_comment->id,
            ]
        )
            ->assertGraphQLErrorMessage('UNAUTHORIZED');
    }

    /**
     * @return void
     */
    public function testUsersCanDeleteTheirOwnInlineComments()
    {
        $admin = $this->beAppAdmin();
        $submission = $this->createSubmissionWithInlineComment(1, $admin);
        $count_before_deletion = $submission->inlineComments()->count();
        $inline_comment = $submission->inlineComments->first();
        $response = $this->graphQL(
            'mutation DeleteInlineComment ($submission_id: ID! $comment_id: ID!) {
                deleteInlineComment(
                    input: {
                        submission_id: $submission_id,
                        comment_id: $comment_id
                    }
                ) {
                    id
                    inline_comments(trashed: WITH) {
                        id
                        content
                        style_criteria {
                            name
                        }
                    }
                }
            }',
            [
                'submission_id' => $submission->id,
                'comment_id' => $inline_comment->id,
            ]
        );
        $expected_data = [
            'deleteInlineComment' => [
                'id' => (string)$submission->id,
                'inline_comments' => [
                    '0' => [
                        'id' => (string)$inline_comment->id,
                        'content' => 'This comment has been deleted',
                        'style_criteria' => [],
                    ],
                ],
            ],
        ];
        $this->assertSoftDeleted($inline_comment);
        $count_after_deletion = $submission->inlineComments()->count();
        $this->assertEquals($count_before_deletion, 1);
        $this->assertEquals($count_after_deletion, 0);
        $response->assertJsonPath('data', $expected_data);
    }

    /**
     * @return void
     */
    public function testUsersCanDeleteTheirOwnOverallComments()
    {
        $admin = $this->beAppAdmin();
        $submission = $this->createSubmissionWithOverallComment(1, $admin);
        $count_before_deletion = $submission->overallComments()->count();
        $overall_comment = $submission->overallComments->first();
        $response = $this->graphQL(
            'mutation DeleteOverallComment ($submission_id: ID! $comment_id: ID!) {
                deleteOverallComment(
                    input: {
                        submission_id: $submission_id,
                        comment_id: $comment_id
                    }
                ) {
                    id
                    overall_comments(trashed: WITH) {
                        id
                        content
                    }
                }
            }',
            [
                'submission_id' => $submission->id,
                'comment_id' => $overall_comment->id,
            ]
        );
        $expected_data = [
            'deleteOverallComment' => [
                'id' => (string)$submission->id,
                'overall_comments' => [
                    '0' => [
                        'id' => (string)$overall_comment->id,
                        'content' => 'This comment has been deleted',
                    ],
                ],
            ],
        ];
        $this->assertSoftDeleted($overall_comment);
        $count_after_deletion = $submission->overallComments()->count();
        $this->assertEquals($count_before_deletion, 1);
        $this->assertEquals($count_after_deletion, 0);
        $response->assertJsonPath('data', $expected_data);
    }

    /**
     * @return void
     */
    public function testUsersCannotDeleteTheInlineCommentsOfOthers(): void
    {
        $this->beAppAdmin();
        $submission = $this->createSubmissionWithInlineComment();
        $inline_comment = $submission->inlineComments->first();
        $this->graphQL(
            'mutation DeleteInlineComment ($submission_id: ID! $comment_id: ID!) {
                deleteInlineComment(
                    input: {
                        submission_id: $submission_id,
                        comment_id: $comment_id
                    }
                ) {
                    id
                    inline_comments(trashed: WITH) {
                        id
                        content
                        style_criteria {
                            name
                        }
                    }
                }
            }',
            [
                'submission_id' => $submission->id,
                'comment_id' => $inline_comment->id,
            ]
        )
            ->assertGraphQLErrorMessage('UNAUTHORIZED');
    }

    /**
     * @return void
     */
    public function testUsersCannotDeleteTheOverallCommentsOfOthers(): void
    {
        $this->beAppAdmin();
        $submission = $this->createSubmissionWithOverallComment();
        $overall_comment = $submission->overallComments->first();
        $this->graphQL(
            'mutation DeleteOverallComment ($submission_id: ID! $comment_id: ID!) {
                deleteOverallComment(
                    input: {
                        submission_id: $submission_id,
                        comment_id: $comment_id
                    }
                ) {
                    id
                    overall_comments(trashed: WITH) {
                        id
                        content
                    }
                }
            }',
            [
                'submission_id' => $submission->id,
                'comment_id' => $overall_comment->id,
            ]
        )
            ->assertGraphQLErrorMessage('UNAUTHORIZED');
    }

    /**
     * @return void
     */
    public function testDeleteInlineCommentReply()
    {
        $admin = $this->beAppAdmin();
        $submission = $this->createSubmissionWithInlineComment();
        $inline_comment = $submission->inlineComments()->first();
        $time = Carbon::parse($inline_comment->created_at);
        $datetime = $this->faker->dateTimeBetween($time, Carbon::now());
        $reply = InlineComment::factory()->create([
            'submission_id' => $submission->id,
            'parent_id' => $inline_comment->id,
            'reply_to_id' => $inline_comment->id,
            'created_at' => $datetime,
            'updated_at' => $datetime,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);
        $count_before_deletion = $submission->inlineComments()->count();
        $response = $this->graphQL(
            'mutation DeleteInlineComment ($submission_id: ID! $comment_id: ID!) {
                deleteInlineComment(
                    input: {
                        submission_id: $submission_id,
                        comment_id: $comment_id
                    }
                ) {
                    id
                    inline_comments {
                        id
                        content
                        style_criteria {
                            name
                        }
                        replies(trashed: WITH) {
                            id
                            content
                        }
                    }
                }
            }',
            [
                'submission_id' => $submission->id,
                'comment_id' => $reply->id,
            ]
        );
        $expected_data = [
            'deleteInlineComment' => [
                'id' => (string)$submission->id,
                'inline_comments' => [
                    '0' => [
                        'id' => (string)$inline_comment->id,
                        'content' => 'This is some content for an inline comment created by PHPUnit.',
                        'style_criteria' => [
                            '0' => [
                                'name' => 'PHPUnit Criteria',
                            ],
                        ],
                        'replies' => [
                            '0' => [
                                'id' => (string)$reply->id,
                                'content' => 'This comment has been deleted',
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $this->assertSoftDeleted($reply);
        $count_after_deletion = $submission->inlineComments()->count();
        $this->assertEquals($count_before_deletion, 1);
        $this->assertEquals($count_after_deletion, 1);
        $response->assertJsonPath('data', $expected_data);
    }

    /**
     * @return void
     */
    public function testDeleteOverallCommentReply()
    {
        $admin = $this->beAppAdmin();
        $submission = $this->createSubmissionWithOverallComment();
        $overall_comment = $submission->overallComments()->first();
        $time = Carbon::parse($overall_comment->created_at);
        $datetime = $this->faker->dateTimeBetween($time, Carbon::now());
        $reply = OverallComment::factory()->create([
            'submission_id' => $submission->id,
            'parent_id' => $overall_comment->id,
            'reply_to_id' => $overall_comment->id,
            'created_at' => $datetime,
            'updated_at' => $datetime,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);
        $count_before_deletion = $submission->overallComments()->count();
        $response = $this->graphQL(
            'mutation DeleteOverallComment ($submission_id: ID! $comment_id: ID!) {
                deleteOverallComment(
                    input: {
                        submission_id: $submission_id,
                        comment_id: $comment_id
                    }
                ) {
                    id
                    overall_comments {
                        id
                        content
                        replies(trashed: WITH) {
                            id
                            content
                        }
                    }
                }
            }',
            [
                'submission_id' => $submission->id,
                'comment_id' => $reply->id,
            ]
        );
        $expected_data = [
            'deleteOverallComment' => [
                'id' => (string)$submission->id,
                'overall_comments' => [
                    '0' => [
                        'id' => (string)$overall_comment->id,
                        'content' => 'This is some content for an overall comment created by PHPUnit.',
                        'replies' => [
                            '0' => [
                                'id' => (string)$reply->id,
                                'content' => 'This comment has been deleted',
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $this->assertSoftDeleted($reply);
        $count_after_deletion = $submission->overallComments()->count();
        $this->assertEquals($count_before_deletion, 1);
        $this->assertEquals($count_after_deletion, 1);
        $response->assertJsonPath('data', $expected_data);
    }

    /**
     * @return void
     */
    public function testInlineCommentCanBeMarkedRead()
    {
        $this->beAppAdmin();
        $submission = $this->createSubmissionWithInlineComment(3);
        $inline_comment_1 = $submission->inlineComments->first();
        $inline_comment_3 = $submission->inlineComments->slice(2, 1)->first();
        $inline_comment_1->markRead();
        $inline_comment_3->markRead();
        $response = $this->graphQL(
            'mutation MarkInlineCommentsRead($submission_id: ID!, $comment_ids: [ID!]!) {
                markInlineCommentsRead (
                    input: {
                        submission_id: $submission_id, comment_ids: $comment_ids
                    }
                ) {
                    id
                    read_at
                }
            }',
            [
                'submission_id' => $submission->id,
                'comment_ids' => [$inline_comment_1->id, $inline_comment_3->id],
            ]
        );
        $expected_data = [
            'markInlineCommentsRead' => [
                '0' => [
                    'id' => (string)$inline_comment_1->id,
                    'read_at' => $inline_comment_1->readAt->format('Y-m-d\TH:i:s.u\Z'),
                ],
                '1' => [
                    'id' => (string)$inline_comment_3->id,
                    'read_at' => $inline_comment_3->readAt->format('Y-m-d\TH:i:s.u\Z'),
                ],
            ],
        ];
        $response->assertJsonPath('data', $expected_data);
    }

    /**
     * @return void
     */
    public function testOverallCommentCanBeMarkedRead()
    {
        $this->beAppAdmin();
        $submission = $this->createSubmissionWithOverallComment(3);
        $overall_comment_1 = $submission->overallComments->first();
        $overall_comment_3 = $submission->overallComments->slice(2, 1)->first();
        $overall_comment_1->markRead();
        $overall_comment_3->markRead();
        $read_status_1 = $overall_comment_1->readAt->format('Y-m-d\TH:i:s.u\Z');
        $read_status_3 = $overall_comment_3->readAt->format('Y-m-d\TH:i:s.u\Z');
        $response = $this->graphQL(
            'mutation MarkOverallCommentsRead($submission_id: ID!, $comment_ids: [ID!]!) {
                markOverallCommentsRead (
                    input: {
                        submission_id: $submission_id, comment_ids: $comment_ids
                    }
                ) {
                    id
                    read_at
                }
            }',
            [
                'submission_id' => $submission->id,
                'comment_ids' => [$overall_comment_1->id, $overall_comment_3->id],
            ]
        );
        $expected_data = [
            'markOverallCommentsRead' => [
                '0' => [
                    'id' => (string)$overall_comment_1->id,
                    'read_at' => $read_status_1,
                ],
                '1' => [
                    'id' => (string)$overall_comment_3->id,
                    'read_at' => $read_status_3,
                ],
            ],
        ];
        $response->assertJsonPath('data', $expected_data);
    }

    /**
     * @return array
     */
    public static function commentReadStatusProvider(): array
    {
        return [
            'read' => [ true ],
            'unread' => [ false ],
        ];
    }

    /**
     * @dataProvider commentReadStatusProvider
     * @param bool $is_read
     * @return void
     */
    public function testInlineCommentReadStatusCanBeQueried(bool $is_read)
    {
        $this->beAppAdmin();
        $submission = $this->createSubmissionWithInlineComment();
        $inline_comment = $submission->inlineComments()->first();
        if ($is_read) {
            $inline_comment->read_at = true;
            $read_status = $inline_comment->readAt->format('Y-m-d\TH:i:s.u\Z');
        } else {
            $read_status = null;
        }
        $response = $this->graphQL(
            'query GetSubmission($id: ID!) {
                submission (id: $id) {
                    id
                    inline_comments {
                        id
                        read_at
                    }
                }
            }',
            ['id' => $submission->id]
        );
        $expected_data = [
            'submission' => [
                'id' => (string)$submission->id,
                'inline_comments' => [
                    '0' => [
                        'id' => (string)$inline_comment->id,
                        'read_at' => $read_status,
                    ],
                ],
            ],
        ];
        $response->assertJsonPath('data', $expected_data);
    }

    /**
     * @dataProvider commentReadStatusProvider
     * @param bool $is_read
     * @return void
     */
    public function testOverallCommentReadStatusCanBeQueried(bool $is_read)
    {
        $this->beAppAdmin();
        $submission = $this->createSubmissionWithOverallComment();
        $overall_comment = $submission->overallComments->first();
        if ($is_read) {
            $overall_comment->read_at = true;
            $read_status = $overall_comment->readAt->format('Y-m-d\TH:i:s.u\Z');
        } else {
            $read_status = null;
        }
        $response = $this->graphQL(
            'query GetSubmission($id: ID!) {
                submission (id: $id) {
                    id
                    overall_comments {
                        id
                        read_at
                    }
                }
            }',
            ['id' => $submission->id]
        );
        $expected_data = [
            'submission' => [
                'id' => (string)$submission->id,
                'overall_comments' => [
                    '0' => [
                        'id' => (string)$overall_comment->id,
                        'read_at' => $read_status,
                    ],
                ],
            ],
        ];
        $response->assertJsonPath('data', $expected_data);
    }
}
