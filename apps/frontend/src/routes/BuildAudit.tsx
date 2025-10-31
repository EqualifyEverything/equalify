import React, {
  useState,
  FormEvent
} from "react";
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
    const response = await (
      await API.post({
        apiName: "auth",
        path: "/saveAudit",
        options: { body: { ...auditData, saveAndRun: true } },
      }).response
    ).body.json() as { id:string };
    setAriaAnnounceMessage(`Audit saved and audit run started!`);
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

    const response = await (
      await API.post({
        apiName: "auth",
        path: "/saveAudit",
        options: { body: { ...auditData, saveAndRun: false } },
      }).response
    ).body.json() as { id:string };
    setAriaAnnounceMessage(`Audit saved!`);
    navigate(`/audits/${response?.id}`);
    return;
  };

  const validateAuditName = (e: React.ChangeEvent<HTMLInputElement>) => {
    const auditName = e.target.value.trim();
    if(auditName) { 
      setAuditNameValid(true)
    } else {
      setAuditNameValid(false)
    };
  }

  return (
    <div className="flex flex-col gap-4 max-w-screen-sm">
      <Link to="..">← Go Back</Link>
      <h1 className="initial-focus-element">Audit Builder</h1>
      <form className="flex flex-col gap-4" onSubmit={saveAndRunAudit}>
        <h2>General Info</h2>
        <div className="flex flex-col">
          <label htmlFor="auditName">Audit Name <span>(required)</span>:</label>
          <input id="auditName" name="auditName" onBlur={validateAuditName} required />
        </div>
        <div className="flex flex-col">
          <label htmlFor="scanFrequency">Scan Frequency:</label>
          <select id="scanFrequency" name="scanFrequency">
            <option>Manually</option>
            <option>Daily</option>
            <option>Weekly</option>
            <option>Monthly</option>
            <option>On Monitor Update</option>
          </select>
        </div>
        <div className="flex flex-col">
          <label htmlFor="emailNotifications">Email Notifications:</label>
          <div>
            <input
              type="checkbox"
              id="emailNotifications"
              name="emailNotifications"
              checked={emailNotifications}
              onChange={(e) => setEmailNotifications(e.target.checked)}
            />
            <label htmlFor="emailNotifications">
              Enable email notifications?
            </label>
          </div>
        </div>
        {emailNotifications && (
          <div className="flex flex-col">
            <AuditEmailSubscriptionInput
              initialValue={emailList}
              onValueChange={setEmailList}
            />
          </div>
        )}

        <h2>Add URLs</h2>
        <AuditPagesInput initialPages={pages} setParentPages={setPages} />
        <div className="border-[1px] border-border" />
        <div className="flex flex-row gap-2">
          <button
            type="button"
            onClick={saveAudit}
            disabled={pages.length < 1 || !auditNameValid}
            className="w-full"
          >
            Save Audit
          </button>
          <button type="submit" disabled={pages.length < 1 || !auditNameValid} className="w-full">
            Save & Run Audit
          </button>
        </div>
      </form>
    </div>
  );
};
