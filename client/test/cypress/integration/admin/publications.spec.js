/// <reference types="Cypress" />
/// <reference path="../../support/index.d.ts" />

import "cypress-axe"
import { a11yLogViolations } from '../../support/helpers'

describe("Admin Publications", () => {
  beforeEach(() => {
    cy.task("resetDb")
    cy.login({ email: "applicationadministrator@ccrproject.dev" })
    cy.visit("/admin/publications")
    cy.injectAxe()
  })

  it("creates new publications and updates the publications list", () => {
    cy.dataCy("new_publication_input").type("Publication from Cypress{enter}")
    cy.dataCy("publications_list").contains("Publication from Cypress")
    cy.dataCy("create_publication_notify")
      .should("be.visible")
      .should("have.class", "bg-positive")
    cy.dataCy("new_publication_input").type("Draft Publication from Cypress")
    cy.get(".q-transition--field-message-leave-active").should("not.exist")
    cy.checkA11y(null, null, a11yLogViolations)
  })

  it("prevents publication creation when the name is empty", () => {
    cy.dataCy("new_publication_input").type("{enter}")
    cy.dataCy("publications_list")
    cy.dataCy("name_field_error").should("be.visible")
    cy.dataCy("new_publication_input").type("Draft Publication from Cypress")
    cy.checkA11y(null, null, a11yLogViolations)
  })

  it("prevents publication creation when the name exceeds the maximum length", () => {
    const name_257_characters = "".padEnd(257, "01234567890")
    cy.dataCy("new_publication_input").type(name_257_characters, {
      delay: 0,
    })
    cy.dataCy("name_field_error").should("be.visible")
    cy.get(".q-transition--field-message-leave-active").should("not.exist")
    cy.get(".q-notification--top-enter-active").should("not.exist")
    cy.checkA11y(null, null, a11yLogViolations)
  })

  it("prevents publication creation when the name is not unique", () => {
    cy.dataCy("new_publication_input").type(
      "Duplicate Publication from Cypress{enter}"
    )
    cy.dataCy("create_publication_notify")
      .should("be.visible")
      .should("have.class", "bg-positive")
    cy.dataCy("publications_list").contains(
      "Duplicate Publication from Cypress"
    )
    cy.dataCy("new_publication_input").type(
      "Duplicate Publication from Cypress{enter}"
    )
    cy.dataCy("name_field_error").should("be.visible")
    cy.get(".q-transition--field-message-leave-active").should("not.exist")
    cy.get(".q-notification--top-enter-active").should("not.exist")
    cy.checkA11y(null, null, a11yLogViolations)
  })

  it("should assert the initial load of the page is accessible", () => {
    cy.dataCy("create_new_publication_form")
    cy.checkA11y(null, null, a11yLogViolations)
  })
})
