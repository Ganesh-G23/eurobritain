<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class MarksUpdatedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $examName,
        public string $marksLabel,
        public ?string $teacherName,
        public bool $forParent = false,
        public ?string $studentDisplayName = null,
        public bool $isNewEntry = false,

        public ?int $batchId = null,
        public ?int $examId = null,
        public ?int $classroomId = null,
        /** Child portal user id when {@see $forParent} is true (for deep links). */
        public ?int $childStudentId = null,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $who = $this->teacherName !== null && $this->teacherName !== '' ? $this->teacherName : 'Your teacher';

        if ($this->forParent) {
            $child = trim((string) ($this->studentDisplayName ?? ''));
            $subject = $child !== '' ? $child . '\'s marks' : 'Your child\'s marks';
            if ($this->isNewEntry) {
                $title = 'New marks for your child';
                $message = $who . ' posted ' . $subject . ' for "' . $this->examName . '": ' . $this->marksLabel . '.';
            } else {
                $title = 'Marks updated for your child';
                $message = $who . ' updated ' . $subject . ' for "' . $this->examName . '": ' . $this->marksLabel . '.';
            }

            return [
                'title' => $title,
                'message' => $message,
                'exam_name' => $this->examName,
                'marks_label' => $this->marksLabel,
                'teacher_name' => $this->teacherName,
                'student_name' => $this->studentDisplayName,
                'student_id' => $this->childStudentId,

                'batch_id' => $this->batchId,
                'exam_id' => $this->examId,
                'classroom_id' => $this->classroomId,
            ];
        }

        if ($this->isNewEntry) {
            $title = 'New marks posted';
            $message = $who . ' posted your marks for "' . $this->examName . '": ' . $this->marksLabel . '.';
        } else {
            $title = 'Marks updated';
            $message = $who . ' updated your marks for "' . $this->examName . '": ' . $this->marksLabel . '.';
        }

        return [
            'title' => $title,
            'message' => $message,
            'exam_name' => $this->examName,
            'marks_label' => $this->marksLabel,
            'teacher_name' => $this->teacherName,

            'batch_id' => $this->batchId,
            'exam_id' => $this->examId,
            'classroom_id' => $this->classroomId,
        ];
    }
}
