<?php
declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\TestFactory;

class SubmissionCommentTest extends TestCase
{
    use RefreshDatabase;
    use TestFactory;

    public function testInlineCommentsAreNotRetrievedForASubmissionThatHasNone()
    {
        $submission = $this->createSubmission();
        $this->assertEmpty($submission->inlineComments);
    }

    public function testOverallCommentsAreNotRetrievedForASubmissionThatHasNone()
    {
        $submission = $this->createSubmission();
        $this->assertEmpty($submission->overallComments);
    }

    public function testInlineCommentsCanBeRetrievedBySubmission()
    {
        $submission = $this->createSubmissionWithInlineComment();
        $this->assertEquals(1, $submission->inlineComments->count());
    }

    public function testOverallCommentsCanBeRetrievedBySubmission()
    {
        $submission = $this->createSubmissionWithOverallComment();
        $this->assertEquals(1, $submission->overallComments->count());
    }

    public function testInlineCommentsStartUnread()
    {
        $this->actingAs(User::factory()->create());
        $submission = $this->createSubmissionWithInlineComment();
        $this->assertEquals(null, $submission->inlineComments->first()->read);
    }

    public function testCanSetInlineCommentAsRead()
    {
        $this->actingAs(User::factory()->create());
        $submission = $this->createSubmissionWithInlineComment();
        $comment = $submission->inlineComments->first();
        $comment->read_at = true;
        $submission->save();
        $this->assertInstanceOf(Carbon::class, $comment->readAt);
    }

    public function testInlineCommentThrowsOnReadSetWithoutUser()
    {
        $this->expectException(\Exception::class);
        $submission = $this->createSubmissionWithInlineComment();
        $submission->inlineComments->first()->readAt = true;
    }

    public function testOverallCommentsStartUnread()
    {
        $this->actingAs(User::factory()->create());
        $submission = $this->createSubmissionWithOverallComment();
        $this->assertEquals(null, $submission->overallComments->first()->readAt);
    }

    public function testCanSetOverallCommentAsRead()
    {
        $this->actingAs(User::factory()->create());
        $submission = $this->createSubmissionWithOverallComment();
        $submission->overallComments->first()->readAt = true;
        $submission->save();
        $this->assertInstanceOf(Carbon::class, $submission->overallComments->first()->readAt);
    }

    public function testOverallCommentThrowsOnReadSetWithoutUser()
    {
        $this->expectException(\Exception::class);
        $submission = $this->createSubmissionWithOverallComment();
        $submission->overallComments->first()->readAt = true;
    }
}
