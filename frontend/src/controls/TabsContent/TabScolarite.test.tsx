/*
 * Copyright (c) 2024-2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 */

import React from "react";
import { render, screen } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { describe, it, expect, vi } from "vitest";
import { ScolariteListItem } from "./TabScolarite";
import { IInscription } from "@api";

// ComposanteItem fait un appel via useApi : on le neutralise pour rester
// strictement sur le rendu des badges OBC-3.
vi.mock("@controls/Items/ComposanteItem", () => ({
  ComposanteItem: () => <div data-testid="composante-item" />,
}));

function makeInscription(overrides: Partial<IInscription> = {}): IInscription {
  return {
    "@id": "/inscriptions/1",
    formation: {
      "@id": "/formations/1",
      libelle: "Licence Informatique",
      composante: "/composantes/1",
    },
    debut: "2025-09-01T00:00:00+02:00",
    fin: "2026-08-31T00:00:00+02:00",
    codeEtape: "INFO1",
    niveau: "L1",
    ...overrides,
  } as IInscription;
}

describe("ScolariteListItem — badges OBC-3", () => {
  it("n'affiche ni Redoublant ni Cursus aménagé quand l'inscription ne porte aucun de ces flags", () => {
    render(<ScolariteListItem inscription={makeInscription()} />);

    expect(screen.getByText("L1")).toBeInTheDocument();
    expect(screen.queryByText("Redoublant")).toBeNull();
    expect(screen.queryByText("Cursus aménagé")).toBeNull();
  });

  it("affiche le badge Redoublant quand inscription.redoublant === true", () => {
    render(<ScolariteListItem inscription={makeInscription({ redoublant: true })} />);

    expect(screen.getByText("Redoublant")).toBeInTheDocument();
    expect(screen.queryByText("Cursus aménagé")).toBeNull();
  });

  it("affiche le badge Cursus aménagé quand inscription.cursusAmenage est défini", () => {
    render(
      <ScolariteListItem
        inscription={makeInscription({
          cursusAmenage: { code: "SPORT_HN", libelle: "Sport haut niveau" },
        })}
      />,
    );

    expect(screen.getByText("Cursus aménagé")).toBeInTheDocument();
    expect(screen.queryByText("Redoublant")).toBeNull();
  });

  it("affiche les deux badges quand redoublant ET cursus aménagé sont positionnés", () => {
    // Cas rare mais possible dans les données Apogée brutes : un redoublant
    // marqué côté SI alors qu'un cursus aménagé est également renseigné.
    // Le backend exclut normalement ce cas via RedoublementCalculator, mais
    // le front doit rester robuste si jamais l'API expose les deux.
    render(
      <ScolariteListItem
        inscription={makeInscription({
          redoublant: true,
          cursusAmenage: { code: "SPORT_HN", libelle: "Sport haut niveau" },
        })}
      />,
    );

    expect(screen.getByText("Redoublant")).toBeInTheDocument();
    expect(screen.getByText("Cursus aménagé")).toBeInTheDocument();
  });

  it("expose le libellé du cursus aménagé via le tooltip (apparition au hover)", async () => {
    const user = userEvent.setup();
    render(
      <ScolariteListItem
        inscription={makeInscription({
          cursusAmenage: { code: "SPORT_HN", libelle: "Sport haut niveau" },
        })}
      />,
    );

    // Ant Design ne pose pas l'attribut HTML `title` ; le contenu du Tooltip
    // n'est inséré dans le DOM qu'au survol/focus. On le déclenche pour
    // vérifier que le libellé "Sport haut niveau" est bien transmis.
    await user.hover(screen.getByText("Cursus aménagé"));

    expect(await screen.findByRole("tooltip")).toHaveTextContent("Sport haut niveau");
  });

  it("retombe sur le code du cursus aménagé si le libellé n'est pas renseigné", async () => {
    const user = userEvent.setup();
    render(
      <ScolariteListItem
        inscription={makeInscription({
          cursusAmenage: { code: "SPORT_HN", libelle: null },
        })}
      />,
    );

    await user.hover(screen.getByText("Cursus aménagé"));

    expect(await screen.findByRole("tooltip")).toHaveTextContent("SPORT_HN");
  });
});
