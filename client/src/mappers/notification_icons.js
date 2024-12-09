import { flatten } from "flat"

const icons = flatten({
  submission: {
    initially_submitted: "content_copy",
    resubmitted: "redo",
    resubmission_requested: "undo",
    awaiting_review: "task_alt",
    rejected: "do_disturb_alt",
    accepted_as_final: "task_alt",
    expired: "schedule",
    under_review: "start",
    awaiting_decision: "bookmark_added",
    revision_requested: "undo",
    archived: "inventory_2",
    deleted: "delete",
    inline_comment: {
      added: "chat_bubble",
    },
    inline_comment_reply: {
      added: "chat_bubble",
    },
    overall_comment: {
      added: "chat",
    },
    overall_comment_reply: {
      added: "chat",
    },
    invitation: {
      review_coordinator: {
        accepted: "emoji_people",
      },
      reviewer: {
        accepted: "emoji_people",
      },
    },
  },
})

export default function getIcon(type) {
  return icons[type] ?? "help"
}
