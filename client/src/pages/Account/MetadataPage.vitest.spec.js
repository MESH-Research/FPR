import { installQuasarPlugin } from "@quasar/quasar-app-extension-testing-unit-vitest"
import { ApolloClients } from "@vue/apollo-composable"
import { mount, flushPromises } from "@vue/test-utils"
import { createMockClient } from "test/vitest/apolloClient"
import { UPDATE_PROFILE_METADATA } from "src/graphql/mutations"
import { CURRENT_USER_METADATA } from "src/graphql/queries"
import { ref as mockRef } from "vue"
import MetadataPage from "./MetadataPage.vue"

import { beforeEach, describe, expect, test, vi } from "vitest"

vi.mock("src/use/forms", async (importOriginal) => {
  const forms = await importOriginal()
  return {
    ...forms,
    useDirtyGuard: () => { },
    useFormState: () => ({
      dirty: mockRef(false),
      saved: mockRef(false),
      state: mockRef("idle"),
      queryLoading: mockRef(false),
      mutationLoading: mockRef(false),
      errorMessage: mockRef(""),
    }),
  }
})

installQuasarPlugin()
describe("MetadataPage", () => {
  const mockClient = createMockClient()
  const makeWrapper = async () => {
    const wrapper = mount(MetadataPage, {
      global: {
        provide: {
          [ApolloClients]: { default: mockClient },
        },
        mocks: {
          $t: (t) => t,
        },
        stubs: ["profile-metadata-form"],
      },
    })
    await flushPromises()
    return wrapper
  }

  const queryHandler = vi.fn()
  const mutateHandler = vi.fn()
  mockClient.setRequestHandler(CURRENT_USER_METADATA, queryHandler)
  mockClient.setRequestHandler(UPDATE_PROFILE_METADATA, mutateHandler)

  const profileData = () => ({
    id: 1,
    profile_metadata: {
      biography: "my bio",
      professional_title: "my title",
      specialization: "my specialization",
      affiliation: "my affiliation",
      website: ["http://mywebsite.com"],
      interest_keywords: ["interest1", "interest2"],
      social_media: {
        twitter: "my_twitter",
        instagram: "my_insta",
        facebook: "my_facebook",
        linked_in: "my_linkedin",
      },
      academic_profiles: {
        humanities_commons: "",
        orcid_id: "",
        academia_edu_id: "",
      },
    },
  })

  beforeEach(() => {
    vi.resetAllMocks()
  })

  test("able to mount", async () => {
    const wrapper = await makeWrapper()
    expect(wrapper).toBeTruthy()
  })

  test("saves profile metadata", async () => {
    const initialData = profileData()

    const newData = profileData()
    newData.profile_metadata.biography = "new biography"

    queryHandler.mockResolvedValue({ data: { currentUser: initialData } })
    mutateHandler.mockResolvedValue({ data: { updateUser: newData } })

    const wrapper = await makeWrapper()
    await wrapper
      .findComponent({ ref: "form" })
      .vm.$emit("save", newData.profile_metadata)

    expect(queryHandler).toHaveBeenCalledTimes(1)
    expect(mutateHandler).toHaveBeenCalledWith({
      id: newData.id,
      ...newData.profile_metadata,
    })
  })

  test("sets error on failure", async () => {
    const pData = profileData()
    queryHandler.mockResolvedValue({ data: { currentUser: pData } })
    mutateHandler.mockRejectedValue({})

    const wrapper = await makeWrapper()
    await wrapper
      .findComponent({ ref: "form" })
      .vm.$emit("save", pData.profile_metadata)
    await flushPromises()

    expect(wrapper.vm.formState.errorMessage.value).not.toBe("")
  })
})
