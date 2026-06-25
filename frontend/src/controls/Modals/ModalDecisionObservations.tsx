/*
 * Copyright (c) 2024-2026. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 */

import { App, Form, Input, Modal } from "antd";
import React, { useEffect } from "react";
import { useApi } from "@context/api/ApiProvider";
import { QK_BENEFICIAIRES, QK_UTILISATEURS_DECISIONS, QK_UTILISATEURS_ITEM } from "@api";

type ObservationsForm = {
  observations: string | null;
};

interface ModalDecisionObservationsProps {
  open: boolean;
  setOpen: (open: boolean) => void;
  decisionId: string;
  utilisateurId: string;
}

/**
 * Permet à un gestionnaire de saisir / modifier le champ libre
 * "Observations particulières" rattaché à une décision d'aménagement d'examens.
 *
 * Le champ est exposé via le DTO `DecisionAmenagementExamens` côté backend
 * (groupes `decision:in` et `decision:out`) et persisté en colonne TEXT.
 */
export function ModalDecisionObservations({
  open,
  setOpen,
  decisionId,
  utilisateurId,
}: ModalDecisionObservationsProps) {
  const { message } = App.useApp();
  const [form] = Form.useForm<ObservationsForm>();

  const { data: decision, isFetching } = useApi().useGetItem({
    path: "/utilisateurs/{uid}/decisions/{annee}",
    url: decisionId,
    enabled: open && !!decisionId,
  });

  const mutateDecision = useApi().usePatch({
    path: "/utilisateurs/{uid}/decisions/{annee}",
    invalidationQueryKeys: [
      QK_BENEFICIAIRES,
      QK_UTILISATEURS_ITEM,
      QK_UTILISATEURS_DECISIONS,
      utilisateurId,
    ],
    onSuccess: () => {
      message.success("Observations enregistrées").then();
      setOpen(false);
    },
    onError: () => {
      message.error("Erreur lors de l'enregistrement des observations").then();
    },
  });

  useEffect(() => {
    if (open && decision) {
      form.setFieldsValue({ observations: decision.observations ?? "" });
    }
  }, [open, decision, form]);

  function handleSubmit(values: ObservationsForm) {
    const observations = values.observations?.trim() ? values.observations.trim() : null;
    mutateDecision.mutate({
      "@id": decisionId,
      data: { observations },
    });
  }

  return (
    <Modal
      open={open}
      onCancel={() => setOpen(false)}
      onOk={() => form.submit()}
      okText="Enregistrer"
      cancelText="Annuler"
      confirmLoading={mutateDecision.isPending}
      title="Observations particulières"
      width={640}
    >
      <Form<ObservationsForm>
        layout="vertical"
        form={form}
        onFinish={handleSubmit}
        initialValues={{ observations: "" }}
      >
        <Form.Item
          name="observations"
          label="Observations particulières"
          extra="Texte libre. Sera repris sur la décision d'établissement transmise au bénéficiaire."
        >
          <Input.TextArea
            rows={4}
            maxLength={4000}
            showCount
            disabled={isFetching}
            placeholder="Saisir les observations particulières liées à cette décision..."
          />
        </Form.Item>
      </Form>
    </Modal>
  );
}
