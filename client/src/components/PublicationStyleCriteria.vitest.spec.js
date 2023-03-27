import { installQuasarPlugin } from "@quasar/quasar-app-extension-testing-unit-vitest"
import { ApolloClients } from "@vue/apollo-composable"
import { mount } from "@vue/test-utils"
import flushPromises from "flush-promises"
import { createMockClient } from "mock-apollo-client"
import {
    CREATE_PUBLICATION_STYLE_CRITERIA,
    DELETE_PUBLICATION_STYLE_CRITERIA,
    UPDATE_PUBLICATION_STYLE_CRITERIA,
} from "src/graphql/mutations"
import PublicationStyleCriteria from "./PublicationStyleCriteria.vue"

installQuasarPlugin()

describe("PublicationStyleCriteria", () => {
  const mockClient = createMockClient()
  const makeWrapper = (publication) => {
    return mount(PublicationStyleCriteria, {
      global: {
        provide: {
          [ApolloClients]: { default: mockClient },
        },
        mocks: {
          $t: (t) => t,
        },
        stubs: ["QEditor"],
      },
      props: {
        publication,
      },
    })
  }

  const updateCriteriaHandler = jest.fn()
  mockClient.setRequestHandler(
    UPDATE_PUBLICATION_STYLE_CRITERIA,
    updateCriteriaHandler
  )
  const createCriteriaHandler = jest.fn()
  mockClient.setRequestHandler(
    CREATE_PUBLICATION_STYLE_CRITERIA,
    createCriteriaHandler
  )

  const deleteCriteriaHandler = jest.fn()
  mockClient.setRequestHandler(
    DELETE_PUBLICATION_STYLE_CRITERIA,
    deleteCriteriaHandler
  )

  beforeEach(() => {
    jest.resetAllMocks()
  })

  test("able to mount", () => {
    const wrapper = makeWrapper({ id: 1 })
    expect(wrapper).toBeTruthy()
  })

  test("displays existing criteria", () => {
    const wrapper = makeWrapper({
      id: "1",
      style_criterias: [
        { id: "1", name: "Criteria 1", description: "Description 1" },
        { id: "2", name: "Criteria 2", description: "Description 2" },
      ],
    })

    const items = wrapper.findAllComponents('[data-cy="listItem"]')
    expect(items).toHaveLength(2)
    expect(items.at(0).text()).toContain("Criteria 1")
    expect(items.at(1).text()).toContain("Criteria 2")
  })

  test("able to edit item", async () => {
    updateCriteriaHandler.mockResolvedValue({
      data: { updatePublication: { id: "1", style_criterias: [] } },
    })
    const wrapper = makeWrapper({
      id: "1",
      style_criterias: [
        { id: "1", name: "Criteria 1", description: "Description 1" },
        { id: "2", name: "Criteria 2", description: "Description 2" },
      ],
    })
    const items = () => wrapper.findAllComponents('[data-cy="listItem"]')
    await items().at(0).findComponent('[data-cy="editBtn').trigger("click")

    const editItem = items().at(0)
    expect(editItem.findComponent({ name: "QForm" }).exists()).toBe(true)

    await editItem
      .findComponent({ name: "QInput" })
      .setValue("Updated Criteria 1")
    await editItem.findComponent({ name: "QForm" }).trigger("submit")
    await flushPromises()
    expect(updateCriteriaHandler).toHaveBeenCalledTimes(1)
    expect(updateCriteriaHandler).toHaveBeenCalledWith({
      description: "Description 1",
      icon: "task_alt",
      id: "1",
      name: "Updated Criteria 1",
      publication_id: "1",
    })

    expect(wrapper.findComponent({ name: "QForm" }).exists()).toBe(false)
  })

  test("able to add item", async () => {
    createCriteriaHandler.mockResolvedValue({
      data: { updatePublication: { id: "1", style_criterias: [] } },
    })
    const wrapper = makeWrapper({
      id: "2",
      style_criterias: [],
    })

    await wrapper.findComponent({ ref: "addBtn" }).trigger("click")
    const addItemComponent = wrapper.findComponent({ ref: "addForm" })
    expect(addItemComponent.exists()).toBe(true)

    await addItemComponent
      .findComponent({ name: "QInput" })
      .setValue("New Criteria 1")
    await addItemComponent.findComponent({ name: "QForm" }).trigger("submit")
    await flushPromises()

    expect(createCriteriaHandler).toHaveBeenCalledTimes(1)
    expect(createCriteriaHandler).toHaveBeenCalledWith({
      description: "",
      icon: "task_alt",
      id: "",
      name: "New Criteria 1",
      publication_id: "2",
    })

    expect(wrapper.findComponent({ name: "QForm" }).exists()).toBe(false)
  })

  test("able to delete item", async () => {
    const wrapper = makeWrapper({
      id: "1",
      style_criterias: [
        { id: "1", name: "Criteria 1", description: "Description 1" },
        { id: "2", name: "Criteria 2", description: "Description 2" },
      ],
    })
    const items = () => wrapper.findAllComponents('[data-cy="listItem"]')
    await items().at(0).findComponent('[data-cy="editBtn').trigger("click")
    await items().at(0).vm.$emit("delete", { id: "1" })

    expect(deleteCriteriaHandler).toHaveBeenCalledTimes(1)
    expect(deleteCriteriaHandler).toHaveBeenCalledWith({
      id: "1",
      publication_id: "1",
    })
  })
})
