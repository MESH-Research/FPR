import gql from "graphql-tag"

export const _PROFILE_METADATA_FIELDS = gql`
  fragment profileMetadata on User {
    profile_metadata {
      biography
      position_title
      specialization
      affiliation
      websites
      social_media {
        twitter
        instagram
        facebook
        linkedin
      }
      academic_profiles {
        humanities_commons
        orcid_id
      }
    }
  }
`

export const _CURRENT_USER_FIELDS = gql`
  fragment currentUserFields on User {
    display_label
    username
    name
    email
    email_verified_at
    roles {
      name
    }
    highest_privileged_role
  }
`

export const _RELATED_USER_FIELDS = gql`
  fragment relatedUserFields on User {
    id
    display_label
    username
    name
    email
    staged
  }
`

export const _PAGINATION_FIELDS = gql`
  fragment paginationFields on PaginatorInfo {
    currentPage
    lastPage
    perPage
    hasMorePages
    count
    total
  }
`

export const _COMMENT_FIELDS = gql`
  fragment commentFields on Comment {
    id
    content
    created_at
    updated_at
    deleted_at
    updated_by {
      ...relatedUserFields
    }
    created_by {
      ...relatedUserFields
    }
  }
  ${_RELATED_USER_FIELDS}
`
