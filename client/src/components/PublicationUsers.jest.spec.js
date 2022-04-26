import { mount } from "@vue/test-utils"
import { installQuasarPlugin } from "@quasar/quasar-app-extension-testing-unit-jest"
import PublicationUsers from "./PublicationUsers.vue"
import { ApolloClients } from "@vue/apollo-composable"
import { createMockClient } from "mock-apollo-client"
import { nextTick } from "vue"
import flushPromises from "flush-promises"
import RoleMapper from "src/mappers/roles"
import {
  CREATE_PUBLICATION_USER,
  DELETE_PUBLICATION_USER,
} from "src/graphql/mutations"
import { GET_PUBLICATION } from "src/graphql/queries"

const mockNewStatus = jest.fn()
jest.mock("src/use/guiElements", () => ({
  useFeedbackMessages: () => ({
    newStatusMessage: mockNewStatus,
  }),
}))
jest.mock("quasar", () => ({
  ...jest.requireActual("quasar"),
  useQuasar: () => ({
    notify: jest.fn(),
  }),
}))

jest.mock("vue-i18n", () => ({
  useI18n: () => ({
    t: (t) => t,
  }),
}))

installQuasarPlugin()

const publicationData = {
  id: "1",
  name: "Jest Publication",
  is_publicly_visible: true,
  users: [
    {
      email: "jestEditor@ccrproject.dev",
      name: "Jest Editor",
      username: "jestEditor",
      pivot: {
        id: 1,
        role_id: 3,
        user_id: 103,
      },
    },
    {
      email: "jestPublicationAdministrator@ccrproject.dev",
      name: "Jest Publication Admin",
      username: "jestPubAdmin",
      pivot: {
        id: 2,
        role_id: 2,
        user_id: 102,
      },
    },
    {
      email: "jestApplicationAdministrator@ccrproject.dev",
      name: "Jest Application Admin And Editor",
      username: "jestAppAdminEditor",
      pivot: {
        id: 3,
        role_id: 3,
        user_id: 101,
      },
    },
  ],
  style_criterias: [],
}

describe("publication details page mount", () => {
  const mockClient = createMockClient()
  const makeWrapper = () => {
    return mount(PublicationUsers, {
      global: {
        provide: {
          [ApolloClients]: { default: mockClient },
        },
        mocks: {
          $t: (t) => t,
        },
        stubs: ["router-link"],
      },
      props: {
        publication: publicationData,
      },
    })
  }

  const mutateAssignHandler = jest.fn()
  mockClient.setRequestHandler(CREATE_PUBLICATION_USER, mutateAssignHandler)

  const mutateRemoveHandler = jest.fn()
  mockClient.setRequestHandler(DELETE_PUBLICATION_USER, mutateRemoveHandler)

  mockClient.setRequestHandler(
    GET_PUBLICATION,
    jest.fn().mockResolvedValue({ data: { publication: publicationData } })
  )
  const originalWarn = console.warn.bind(console.warn)
  beforeEach(async () => {
    jest.resetAllMocks()

    //refetchQueries warning is thrown when trying to test this outside of the parent component.
    //We override console.warn to silence this warning while still passing on other warnings.
    console.warn = (msg) =>
      !msg.toString().includes("refetchQueries options.include array") &&
      originalWarn(msg)
  })

  afterEach(() => {
    console.warn = originalWarn
  })

  it("mounts without errors", async () => {
    const wrapper = makeWrapper()
    await flushPromises()
    expect(wrapper).toBeTruthy()
  })

  test("all existing editors appear within the editors list", async () => {
    const wrapper = makeWrapper()

    const list = wrapper.findComponent({ ref: "list_assigned_editors" })
    expect(list.findAllComponents({ name: "user-list-item" })).toHaveLength(2)
  })

  test("editors can be assigned", async () => {
    const wrapper = makeWrapper()
    const newPivotData = {
      user_id: "104",
      role_id: RoleMapper["editor"],
      publication_id: "1",
    }
    mutateAssignHandler.mockResolvedValue({
      data: {
        createPublicationUser: {
          id: "1",
        },
      },
    })

    wrapper.vm.editor_candidate = {
      id: "104",
      name: "Jest Editor Candidate Name",
      email: "jestEditorCandidate@ccrproject.dev",
      username: "jestEditorCandidate",
    }
    wrapper.vm.addMode = true
    await nextTick()
    wrapper.findComponent({ ref: "assignBtn" }).trigger("submit")
    await flushPromises()

    expect(mutateAssignHandler).toBeCalledWith(newPivotData)
    expect(mockNewStatus).toBeCalledWith("success", expect.any(String))
  })

  test("failure message on rejection", async () => {
    const wrapper = makeWrapper()
    const newPivotData = {
      user_id: "104",
      role_id: RoleMapper["editor"],
      publication_id: "1",
    }
    mutateAssignHandler.mockRejectedValue({})

    wrapper.vm.editor_candidate = {
      id: "104",
      name: "Jest Editor Candidate Name",
      email: "jestEditorCandidate@ccrproject.dev",
      username: "jestEditorCandidate",
    }
    wrapper.vm.addMode = true
    await nextTick()
    wrapper.findComponent({ ref: "assignBtn" }).trigger("submit")
    await flushPromises()

    expect(mutateAssignHandler).toBeCalledWith(newPivotData)
    expect(mockNewStatus).toBeCalledWith("failure", expect.any(String))
  })

  test("can remove editor", async () => {
    mutateRemoveHandler.mockResolvedValue({
      data: { deletePublicationUser: { id: "103" } },
    })
    const wrapper = makeWrapper()
    await flushPromises()
    wrapper
      .findComponent({ name: "user-list-item" })
      .findComponent({ name: "q-btn" })
      .trigger("click")
    await flushPromises()

    expect(mutateRemoveHandler).toBeCalledWith(
      expect.objectContaining({ user_id: 103 })
    )
    expect(mockNewStatus).toBeCalledWith("success", expect.any(String))
  })
})
