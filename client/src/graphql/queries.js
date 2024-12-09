import gql from "graphql-tag"
import {
  _COMMENT_FIELDS,
  _CURRENT_USER_FIELDS,
  _PAGINATION_FIELDS,
  _PROFILE_METADATA_FIELDS,
  _RELATED_USER_FIELDS,
} from "./fragments"

export const CURRENT_USER = gql`
  ${_CURRENT_USER_FIELDS}
  query CurrentUser {
    currentUser {
      id
      ...currentUserFields
    }
  }
`

export const CURRENT_USER_METADATA = gql`
  ${_PROFILE_METADATA_FIELDS}
  query CurrentUserMetadata {
    currentUser {
      id
      username
      name
      ...profileMetadata
    }
  }
`

export const CURRENT_USER_NOTIFICATIONS = gql`
  query currentUserNotifications($page: Int, $unread: Boolean, $read: Boolean) {
    currentUser {
      id
      notifications(first: 10, page: $page, unread: $unread, read: $read) {
        paginatorInfo {
          ...paginationFields
        }
        data {
          id
          read_at
          created_at
          data {
            user {
              username
            }
            submission {
              title
            }
            commentor {
              display_label
            }
            invitee {
              display_label
            }
            inviter {
              display_label
            }
            publication {
              name
            }
            type
            body
          }
        }
      }
    }
  }
  ${_PAGINATION_FIELDS}
`

export const CURRENT_USER_SUBMISSIONS = gql`
  query CurrentUserSubmissions {
    currentUser {
      id
      roles {
        name
      }
      submissions {
        id
        title
        status
        created_at
        submitted_at
        my_role
        effective_role
        review_coordinators {
          ...relatedUserFields
        }
        reviewers {
          ...relatedUserFields
        }
        submitters {
          ...relatedUserFields
        }
        inline_comments(trashed: WITH) {
          id
          content
          created_by {
            id
            display_label
            email
          }
          updated_by {
            id
            display_label
            email
          }
          created_at
          updated_at
          style_criteria {
            id
            name
            icon
          }
          replies {
            id
            content
            created_by {
              id
              display_label
              email
            }
            updated_by {
              id
              display_label
              email
            }
            created_at
            updated_at
            read_at
          }
          read_at
        }
        overall_comments(trashed: WITH) {
          id
          content
          created_by {
            id
            display_label
            email
          }
          updated_by {
            id
            display_label
            email
          }
          created_at
          updated_at
          replies {
            id
            content
            created_by {
              id
              display_label
              email
            }
            updated_by {
              id
              display_label
              email
            }
            created_at
            updated_at
            read_at
          }
          read_at
        }
        publication {
          id
          name
          my_role
          editors {
            ...relatedUserFields
          }
          publication_admins {
            ...relatedUserFields
          }
        }
      }
    }
  }
  ${_RELATED_USER_FIELDS}
`

export const GET_USERS = gql`
  query GetUsers($page: Int) {
    userSearch(page: $page) {
      paginatorInfo {
        ...paginationFields
      }
      data {
        id
        name
        username
        email
      }
    }
  }
  ${_PAGINATION_FIELDS}
`

export const GET_USER = gql`
  query getUser($id: ID) {
    user(id: $id) {
      username
      email
      name
      roles {
        name
      }
    }
  }
`

export const SEARCH_USERS = gql`
  query SearchUsers($term: String, $page: Int) {
    userSearch(term: $term, page: $page) {
      paginatorInfo {
        ...paginationFields
      }
      data {
        id
        username
        name
        email
      }
    }
  }
  ${_PAGINATION_FIELDS}
`

export const GET_PUBLICATIONS = gql`
  query GetPublications(
    $page: Int
    $first: Int
    $is_publicly_visible: Boolean
    $is_accepting_submissions: Boolean
  ) {
    publications(
      page: $page
      first: $first
      is_publicly_visible: $is_publicly_visible
      is_accepting_submissions: $is_accepting_submissions
    ) {
      paginatorInfo {
        ...paginationFields
      }
      data {
        id
        name
        home_page_content
      }
    }
  }
  ${_PAGINATION_FIELDS}
`

export const GET_SUBMISSIONS = gql`
  query GetSubmissions($page: Int) {
    submissions(page: $page) {
      paginatorInfo {
        ...paginationFields
      }
      data {
        id
        title
        status
        my_role
        created_at
        submitted_at
        effective_role
        submitters {
          ...relatedUserFields
        }
        reviewers {
          ...relatedUserFields
        }
        review_coordinators {
          ...relatedUserFields
        }
        publication {
          id
          name
          my_role
          editors {
            ...relatedUserFields
          }
          publication_admins {
            ...relatedUserFields
          }
        }
      }
    }
  }
  ${_PAGINATION_FIELDS}
  ${_RELATED_USER_FIELDS}
`

export const GET_SUBMISSION = gql`
  query GetSubmission($id: ID!) {
    submission(id: $id) {
      id
      title
      status
      effective_role
      content {
        data
      }
      audits {
        id
        event
        user {
          id
          name
          username
          email
        }
        old_values {
          title
          status
          status_change_comment
          content_id
        }
        new_values {
          title
          status
          status_change_comment
          content_id
        }
        created_at
      }
      publication {
        id
        name
        style_criterias {
          id
          name
          description
          icon
        }
        my_role
        editors {
          ...relatedUserFields
        }
        publication_admins {
          ...relatedUserFields
        }
      }
      submitters {
        ...relatedUserFields
      }
      reviewers {
        ...relatedUserFields
      }
      review_coordinators {
        ...relatedUserFields
      }
    }
  }
  ${_RELATED_USER_FIELDS}
`

export const GET_SUBMISSION_REVIEW = gql`
  query GetSubmissionReview($id: ID!) {
    submission(id: $id) {
      id
      title
      status
      content {
        data
      }
      publication {
        id
        style_criterias {
          id
          name
          description
          icon
        }
        editors {
          ...relatedUserFields
        }
        publication_admins {
          ...relatedUserFields
        }
      }
      inline_comments(trashed: WITH) {
        deleted_at
        from
        to
        ...commentFields
        style_criteria {
          id
          name
          icon
        }
        replies(trashed: WITH) {
          ...commentFields
          parent_id
          reply_to_id
          read_at
        }
        read_at
      }
      overall_comments(trashed: WITH) {
        ...commentFields
        replies(trashed: WITH) {
          ...commentFields
          parent_id
          reply_to_id
          read_at
        }
        read_at
      }
      submitters {
        ...relatedUserFields
      }
      reviewers {
        ...relatedUserFields
      }
      review_coordinators {
        ...relatedUserFields
      }
    }
  }
  ${_COMMENT_FIELDS}
`

export const GET_PUBLICATION = gql`
  query GetPublication($id: ID!) {
    publication(id: $id) {
      id
      name
      is_publicly_visible
      is_accepting_submissions
      effective_role
      home_page_content
      new_submission_content
      style_criterias {
        name
        id
        icon
        description
      }
      publication_admins {
        ...relatedUserFields
      }
      editors {
        ...relatedUserFields
      }
    }
  }
  ${_RELATED_USER_FIELDS}
`

export const GET_IDENTITY_PROVIDERS = gql`
  query GetIdentityProviders {
    identityProviders {
      name
      login_url
      label
      icon
    }
  }
`
