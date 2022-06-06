import gql from "graphql-tag"
import {
  _CURRENT_USER_FIELDS,
  _PROFILE_METADATA_FIELDS,
  _RELATED_USER_FIELDS,
} from "./fragments"

export const LOGIN = gql`
  mutation Login($email: String!, $password: String!) {
    login(email: $email, password: $password) {
      id
      ...currentUserFields
    }
  }
  ${_CURRENT_USER_FIELDS}
`

export const LOGOUT = gql`
  mutation Logout {
    logout {
      id
    }
  }
`

export const CREATE_USER = gql`
  mutation CreateUser(
    $email: String!
    $name: String
    $username: String!
    $password: String!
  ) {
    createUser(
      user: {
        name: $name
        email: $email
        username: $username
        password: $password
      }
    ) {
      username
      id
      created_at
    }
  }
`

export const UPDATE_USER = gql`
  mutation UpdateUser(
    $id: ID!
    $email: String
    $name: String
    $username: String
    $password: String
  ) {
    updateUser(
      user: {
        id: $id
        email: $email
        name: $name
        username: $username
        password: $password
      }
    ) {
      id
      email
      name
      username
      updated_at
    }
  }
`

export const VERIFY_EMAIL = gql`
  mutation VerifyEmail($token: String!, $expires: String!) {
    verifyEmail(token: $token, expires: $expires) {
      email_verified_at
    }
  }
`

export const SEND_VERIFY_EMAIL = gql`
  mutation SendVerificationEmail($id: ID) {
    sendEmailVerification(id: $id) {
      email
    }
  }
`

export const CREATE_PUBLICATION = gql`
  mutation CreatePublication($name: String!) {
    createPublication(publication: { name: $name }) {
      id
      name
    }
  }
`

export const CREATE_PUBLICATION_USER = gql`
  mutation CreatePublicationUser(
    $user_id: ID!
    $role_id: ID!
    $publication_id: ID!
  ) {
    createPublicationUser(
      publication_user: {
        user_id: $user_id
        role_id: $role_id
        publication_id: $publication_id
      }
    ) {
      id
    }
  }
`

export const DELETE_PUBLICATION_USER = gql`
  mutation DeletePublicationUser(
    $user_id: ID!
    $role_id: ID!
    $publication_id: ID!
  ) {
    deletePublicationUser(
      publication_user: {
        user_id: $user_id
        role_id: $role_id
        publication_id: $publication_id
      }
    ) {
      id
    }
  }
`

export const CREATE_SUBMISSION = gql`
  mutation CreateSubmission(
    $title: String!
    $publication_id: ID!
    $submitter_user_id: ID!
    $file_upload: [Upload!]
  ) {
    createSubmission(
      input: {
        title: $title
        publication_id: $publication_id
        submitters: { connect: [$submitter_user_id] }
        files: { create: $file_upload }
      }
    ) {
      id
      title
      publication {
        name
      }
    }
  }
`

export const CREATE_SUBMISSION_FILE = gql`
  mutation CreateSubmissionFile($submission_id: ID!, $file_upload: Upload!) {
    createSubmissionFile(
      input: { submission_id: $submission_id, file_upload: $file_upload }
    ) {
      id
      submission_id
      file_upload
    }
  }
`

export const UPDATE_SUBMISSION_REVIEWERS = gql`
  mutation UpdateSubmissionReviewers(
    $submission_id: ID!
    $connect: [ID!]
    $disconnect: [ID!]
  ) {
    updateSubmission(
      input: {
        id: $submission_id
        reviewers: { connect: $connect, disconnect: $disconnect }
      }
    ) {
      id
      reviewers {
        ...relatedUserFields
      }
    }
  }
  ${_RELATED_USER_FIELDS}
`
export const UPDATE_SUBMISSION_REVIEW_COORDINATORS = gql`
  mutation UpdateSubmissionReviewCoordinators(
    $submission_id: ID!
    $connect: [ID!]
    $disconnect: [ID!]
  ) {
    updateSubmission(
      input: {
        id: $submission_id
        review_coordinators: { connect: $connect, disconnect: $disconnect }
      }
    ) {
      id
      review_coordinators {
        ...relatedUserFields
      }
    }
  }
  ${_RELATED_USER_FIELDS}
`

export const UPDATE_SUBMISSION_SUBMITERS = gql`
  mutation UpdateSubmissionReviewCoordinators(
    $submission_id: ID!
    $connect: [ID!]
    $disconnect: [ID!]
  ) {
    updateSubmission(
      input: {
        id: $submission_id
        submitters: { connect: $connect, disconnect: $disconnect }
      }
    ) {
      id
      submitters {
        ...relatedUserFields
      }
    }
  }
  ${_RELATED_USER_FIELDS}
`

export const UPDATE_PROFILE_METADATA = gql`
  mutation UpdateProfileMetaData(
    $id: ID!
    $affiliation: String
    $biography: String
    $disinterest_keywords: [String!]
    $interest_keywords: [String!]
    $websites: [String!]
    $professional_title: String
    $specialization: String
    $social_media: UpdateSocialMediaInput
    $academic_profiles: UpdateAcademicProfilesInput
  ) {
    updateUser(
      user: {
        id: $id
        profile_metadata: {
          affiliation: $affiliation
          biography: $biography
          disinterest_keywords: $disinterest_keywords
          interest_keywords: $interest_keywords
          websites: $websites
          professional_title: $professional_title
          specialization: $specialization
          social_media: $social_media
          academic_profiles: $academic_profiles
        }
      }
    ) {
      id
      ...profileMetadata
    }
  }
  ${_PROFILE_METADATA_FIELDS}
`

export const MARK_NOTIFICATION_READ = gql`
  mutation MarkNotificationRead($notification_id: ID!) {
    markNotificationRead(id: $notification_id) {
      read_at
    }
  }
`

export const MARK_ALL_NOTIFICATIONS_READ = gql`
  mutation MarkAllNotificationsRead {
    markAllNotificationsRead
  }
`

export const UPDATE_PUBLICATION_STYLE_CRITERIA = gql`
  mutation UpdatePublicationStyleCriteria(
    $publication_id: ID!
    $id: ID!
    $name: String
    $description: String
    $icon: String
  ) {
    updatePublication(
      publication: {
        id: $publication_id
        style_criterias: {
          update: [
            { id: $id, name: $name, description: $description, icon: $icon }
          ]
        }
      }
    ) {
      id
      style_criterias {
        id
        name
        description
        icon
      }
    }
  }
`

export const CREATE_PUBLICATION_STYLE_CRITERIA = gql`
  mutation CreatePublicationStyleCriteria(
    $publication_id: ID!
    $name: String!
    $description: String
    $icon: String
  ) {
    updatePublication(
      publication: {
        id: $publication_id
        style_criterias: {
          create: [{ name: $name, description: $description, icon: $icon }]
        }
      }
    ) {
      id
      style_criterias {
        id
        name
        description
        icon
      }
    }
  }
`

export const DELETE_PUBLICATION_STYLE_CRITERIA = gql`
  mutation DeletePublicationStyleCriteria($publication_id: ID!, $id: ID!) {
    updatePublication(
      publication: { id: $publication_id, style_criterias: { delete: [$id] } }
    ) {
      id
      style_criterias {
        id
        name
        description
        icon
      }
    }
  }
`

export const CREATE_OVERALL_COMMENT = gql`
  mutation AddOverallComment($submission_id: ID!, $content: String!) {
    addOverallComment(submission_id: $submission_id, content: $content) {
      id
      content
      created_at
      created_by {
        id
        email
        name
        username
      }
    }
  }
`
