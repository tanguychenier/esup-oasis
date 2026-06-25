/*
 * Copyright (c) 2024-2026. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 */

import React from "react";
import { render, screen, waitFor } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { App } from "antd";
import { describe, it, expect, vi, beforeEach } from "vitest";
import { ModalDecisionObservations } from "./ModalDecisionObservations";

// ─── Mocks ────────────────────────────────────────────────────────────────────

const { mockUseGetItem, mockMutate, mockUsePatch } = vi.hoisted(() => ({
  mockUseGetItem: vi.fn(),
  mockMutate: vi.fn(),
  mockUsePatch: vi.fn(),
}));

vi.mock("@context/api/ApiProvider", () => ({
  useApi: () => ({
    useGetItem: mockUseGetItem,
    usePatch: mockUsePatch,
  }),
}));

describe("ModalDecisionObservations — formulaire d'observations particulières", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mockUseGetItem.mockReturnValue({
      data: {
        "@id": "/utilisateurs/test@uni.fr/decisions/2026",
        etat: "ATTENTE_VALIDATION_CAS",
        observations: "",
      },
      isFetching: false,
    });
    mockUsePatch.mockReturnValue({ mutate: mockMutate, isPending: false });
  });

  function renderModal(open = true) {
    return render(
      <App>
        <ModalDecisionObservations
          open={open}
          setOpen={vi.fn()}
          decisionId="/utilisateurs/test@uni.fr/decisions/2026"
          utilisateurId="test@uni.fr"
        />
      </App>,
    );
  }

  it("affiche un textarea 'Observations particulières'", async () => {
    renderModal();
    await screen.findByRole("dialog");

    // Le label apparaît au moins deux fois (titre de modale + label du form),
    // on cherche donc le textarea par son rôle.
    const textarea = await screen.findByRole("textbox");
    expect(textarea).toBeInTheDocument();
    expect(textarea.tagName).toBe("TEXTAREA");
  });

  it("accepte la saisie utilisateur et la transmet via PATCH", async () => {
    const user = userEvent.setup();
    renderModal();
    await screen.findByRole("dialog");

    const textarea = await screen.findByRole("textbox");
    await user.click(textarea);
    await user.type(textarea, "Tiers temps validé sur dossier 2026");

    const okButton = screen.getByRole("button", { name: /enregistrer/i });
    await user.click(okButton);

    await waitFor(() => {
      expect(mockMutate).toHaveBeenCalledTimes(1);
    });
    expect(mockMutate).toHaveBeenCalledWith({
      "@id": "/utilisateurs/test@uni.fr/decisions/2026",
      data: { observations: "Tiers temps validé sur dossier 2026" },
    });
  });

  it("envoie null quand la saisie est vide", async () => {
    const user = userEvent.setup();
    mockUseGetItem.mockReturnValue({
      data: {
        "@id": "/utilisateurs/test@uni.fr/decisions/2026",
        etat: "ATTENTE_VALIDATION_CAS",
        observations: "    ",
      },
      isFetching: false,
    });
    renderModal();
    await screen.findByRole("dialog");

    const textarea = await screen.findByRole("textbox");
    await user.clear(textarea);

    const okButton = screen.getByRole("button", { name: /enregistrer/i });
    await user.click(okButton);

    await waitFor(() => {
      expect(mockMutate).toHaveBeenCalledTimes(1);
    });
    expect(mockMutate).toHaveBeenCalledWith({
      "@id": "/utilisateurs/test@uni.fr/decisions/2026",
      data: { observations: null },
    });
  });
});
