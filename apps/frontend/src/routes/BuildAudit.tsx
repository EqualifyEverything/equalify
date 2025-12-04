import React, { useState, FormEvent } from "react";
import { Link, useNavigate } from "react-router-dom";
import { useUser } from "../queries";
import * as API from "aws-amplify/api";
import {
  AuditEmailSubscriptionInput,
  EmailSubscriptionList,
} from "#src/components/AuditEmailSubscriptionInput.tsx";
import { v4 as uuidv4 } from "uuid";
import { useGlobalStore } from "../utils";
import { AuditPagesInput } from "#src/components/AuditPagesInput.tsx";
import { createLog } from "#src/utils/createLog.ts";
import { StyledLabeledInput } from "#src/components/StyledLabeledInput.tsx";
import { Card } from "#src/components/Card.tsx";
import { CgOptions } from "react-icons/cg";
import { TbList, TbMail } from "react-icons/tb";
import { StyledButton } from "#src/components/StyledButton.tsx";
import { LuClipboardCheck, LuClipboardPaste } from "react-icons/lu";
import styles from "./BuildAudit.module.scss";
import * as Switch from "@radix-ui/react-switch";

interface Page {
  url: string;
  type: "html" | "pdf";
}

export const BuildAudit = () => {
  const navigate = useNavigate();
  const { setAriaAnnounceMessage } = useGlobalStore();
  const { data: user } = useUser();

  const [emailNotifications, setEmailNotifications] = useState(false);
  const [pages, setPages] = useState<Page[]>([]);
  const [auditNameValid, setAuditNameValid] = useState(false);

  const defaultEmailList = {
    emails: [
      {
        id: uuidv4(),
        email: user?.email ?? "user@uic.edu",
        frequency: "Weekly",
        lastSent: "", // we'll populate this on send in buildAuditData
      },
    ],
  };
  const [emailList, setEmailList] =
    useState<EmailSubscriptionList>(defaultEmailList);

  const buildAuditData = (formData: FormData) => {
    let notifications: EmailSubscriptionList = { emails: [] };
    if (emailNotifications) {
      // if notification are enabled, add the current date to newly-added emails
      const theDate = new Date().toISOString();
      notifications.emails = emailList.emails.map((item) => {
        return {
          ...item,
          lastSent: theDate,
        };
      });
    }
    return {
      auditName: formData.get("auditName") as string,
      scanFrequency: formData.get("scanFrequency") as string,
      emailNotifications: JSON.stringify(notifications),
      pages: pages.map((page) => ({
        url: page.url,
        type: page.type,
      })),
    };
  };

  const saveAndRunAudit = async (e: FormEvent) => {
    e.preventDefault();
    if (pages.length === 0) {
      window.alert(`You need to add at least 1 URL to your audit.`);
      return;
    }
    const formData = new FormData(e.currentTarget as HTMLFormElement);
    const auditData = buildAuditData(formData);
    console.log("Audit Data (Save & Run):", JSON.stringify(auditData));
    const response = (await (
      await API.post({
        apiName: "auth",
        path: "/saveAudit",
        options: { body: { ...auditData, saveAndRun: true } },
      }).response
    ).body.json()) as { id: string };
    setAriaAnnounceMessage(`Audit saved and audit run started!`);
    await createLog(`Audit created and audit run started!`, response.id);

    navigate(`/audits/${response?.id}`);
    return;
  };

  const saveAudit = async (e: FormEvent) => {
    e.preventDefault();
    if (pages.length === 0) {
      window.alert(`You need to add at least 1 URL to your audit.`);
      return;
    }
    const form = (e.currentTarget as HTMLElement).closest("form");
    if (!form) return;
    const formData = new FormData(form);
    const auditData = buildAuditData(formData);

    console.log("Audit Data (Save):", JSON.stringify(auditData));

    const response = (await (
      await API.post({
        apiName: "auth",
        path: "/saveAudit",
        options: { body: { ...auditData, saveAndRun: false } },
      }).response
    ).body.json()) as { id: string };
    setAriaAnnounceMessage(`Audit saved!`);
    await createLog(`Audit created!`, response.id);
    navigate(`/audits/${response?.id}`);
    return;
  };

  const validateAuditName = (e: React.ChangeEvent<HTMLInputElement>) => {
    const auditName = e.target.value.trim();
    if (auditName) {
      setAuditNameValid(true);
    } else {
      setAuditNameValid(false);
    }
  };

  return (
    <div className={styles.buildAudit}>
      <Link to="..">← Go Back</Link>
      <h1 className="initial-focus-element">Audit Builder</h1>
      <form onSubmit={saveAndRunAudit}>
        <div className="cards-38-62">
        <Card variant="light">
          <h2>
            <CgOptions className={"icon-small"} />
            General Info
          </h2>
          <StyledLabeledInput>
            <label htmlFor="auditName">
              Audit Name <span>(required)</span>:
            </label>
            <input
              id="auditName"
              name="auditName"
              onBlur={validateAuditName}
              required
              className={styles["input-element"]}
            />
          </StyledLabeledInput>

          <StyledLabeledInput>
            <label htmlFor="scanFrequency">Scan Frequency:</label>
            <select id="scanFrequency" name="scanFrequency" className={styles["input-element"]}>
              <option>Manually</option>
              <option>Daily</option>
              <option>Weekly</option>
              <option>Monthly</option>
              <option>On Monitor Update</option>
            </select>
          </StyledLabeledInput>
        </Card>
        <Card variant="light">
          <h2>
            <TbMail className="icon-small" />
            Email Notifications:
          </h2>
          <StyledLabeledInput>  
            <label htmlFor="emailNotifications">
              Enable email notifications?
            </label>
            <Switch.Root
              id="emailNotifications"
              checked={!emailNotifications}
              onCheckedChange={(checked) => setEmailNotifications(!checked)}
              aria-label={"Enable email notifications?"}
              className={styles["switch"]}
            >
              <Switch.Thumb className={styles["switch-thumb"]} />
            </Switch.Root>
          </StyledLabeledInput>

          {emailNotifications && (
            <div>
              <AuditEmailSubscriptionInput
                initialValue={emailList}
                onValueChange={setEmailList}
              />
            </div>
          )}
        </Card>
        </div>

        <Card variant="light">
          <h2>
            <TbList className={"icon-small"} />
            Add URLs
          </h2>
          <AuditPagesInput initialPages={pages} setParentPages={setPages} />
        </Card>
        <div className={styles["action-buttons"]}>
          <StyledButton
            icon={<LuClipboardCheck />}
            onClick={saveAudit}
            label="Save Audit"
            disabled={pages.length < 1 || !auditNameValid}
          />
          <StyledButton
            icon={<LuClipboardPaste />}
            onClick={saveAndRunAudit}
            label="Save & Run Audit"
            variant="red"
            disabled={pages.length < 1 || !auditNameValid}
          />
        </div>
        {/* 
            <button
              type="button"
              onClick={saveAudit}
              disabled={pages.length < 1 || !auditNameValid}
              className="w-full"
            >
              Save Audit
            </button> */}
        {/* <button
              type="submit"
              disabled={pages.length < 1 || !auditNameValid}
              className="w-full"
            >
              Save & Run Audit
            </button> */}
      </form>
    </div>
  );
};
