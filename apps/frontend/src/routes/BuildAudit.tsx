import React, { useState, FormEvent } from "react";
import { Link, useNavigate } from "react-router-dom";
import { useQuery } from "@tanstack/react-query";
import { useUser } from "../queries";
import * as API from "aws-amplify/api";
import {
  AuditEmailSubscriptionInput,
  EmailSubscriptionList,
} from "#src/components/AuditEmailSubscriptionInput.tsx";
//import { v4 as uuidv4 } from "uuid";
import { useGlobalStore } from "../utils";
import { AuditPagesInput } from "#src/components/AuditPagesInput.tsx";
import { createLog } from "#src/utils/createLog.ts";
import { StyledLabeledInput } from "#src/components/StyledLabeledInput.tsx";
import { Card } from "#src/components/Card.tsx";
import { CgOptions } from "react-icons/cg";
import { TbAlertTriangle, TbMail } from "react-icons/tb";
import { StyledButton } from "#src/components/StyledButton.tsx";
import { LuClipboardCheck, LuClipboardPaste, LuImport } from "react-icons/lu";
import styles from "./BuildAudit.module.scss";
//import * as Switch from "@radix-ui/react-switch";
import * as Tabs from "@radix-ui/react-tabs";
import { FaCloudArrowDown, FaFileCirclePlus, FaListUl, FaSpider } from "react-icons/fa6";
import { AuditRemoteCsvInput } from "#src/components/AuditRemoteCsvInput.tsx";
import { AuditCrawlInput } from "#src/components/AuditCrawlInput.tsx";

interface Page {
  url: string;
  type: "html" | "pdf";
}

const apiClient = API.generateClient();
const URL_SOFT_LIMIT = 10_000;
const AUDIT_SOFT_LIMIT = 10_000;

export const BuildAudit = () => {
  const navigate = useNavigate();
  const { setAnnounceMessage } = useGlobalStore();
  const { data: user } = useUser();

  const [emailNotifications, setEmailNotifications] = useState(false);
  const [pages, setPages] = useState<Page[]>([]);
  const [remoteCsvUrl, setRemoteCsvUrl] = useState("");
  const [auditNameValid, setAuditNameValid] = useState(false);
  const [isSaving, setIsSaving] = useState(false);
  const [isSavingAndRunning, setIsSavingAndRunning] = useState(false);
  const [scanFrequency, setScanFrequency] = useState("Manually");

  const [validRemoteCsv, setValidRemoteCsv] = useState(false);

  const { data: auditCount } = useQuery({
    queryKey: ["auditCount"],
    queryFn: async () => {
      const result = (await apiClient.graphql({
        query: `{ audits_aggregate(where: {interval: {_neq: "Quick Scan"}}) { aggregate { count } } }`,
      })) as any;
      return result?.data?.audits_aggregate?.aggregate?.count ?? 0;
    },
  });

  const defaultEmailList = {
    emails: [
      /* {
        id: uuidv4(),
        email: user?.email ?? "user@uic.edu",
        frequency: "Weekly",
        lastSent: "", // we'll populate this on send in buildAuditData
      }, */
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
    /* if (remoteCsvUrl) { // if we're using a remote csv, discard any manual input
      setPages([]);
    } */
    return {
      auditName: formData.get("auditName") as string,
      scanFrequency: formData.get("scanFrequency") as string,
      emailNotifications: JSON.stringify(notifications),
      remoteCsvUrl: remoteCsvUrl,
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
    const form = (e.currentTarget as HTMLElement).closest("form");
    if (!form) return;
    const formData = new FormData(form);
    const auditData = buildAuditData(formData);
    console.log("Audit Data (Save & Run):", JSON.stringify(auditData));
    setIsSavingAndRunning(true);
    try {
      const response = (await (
        await API.post({
          apiName: "auth",
          path: "/saveAudit",
          options: { body: { ...auditData, saveAndRun: true } },
        }).response
      ).body.json()) as { id: string };
      setAnnounceMessage(`Audit saved and audit run started!`, "success");
      await createLog(`Audit created and audit run started!`, response.id);
      navigate(`/audits/${response?.id}`);
    } finally {
      setIsSavingAndRunning(false);
    }
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
    setIsSaving(true);
    try {
      const response = (await (
        await API.post({
          apiName: "auth",
          path: "/saveAudit",
          options: { body: { ...auditData, saveAndRun: false } },
        }).response
      ).body.json()) as { id: string };
      setAnnounceMessage(`Audit saved!`, "success");
      await createLog(`Audit created!`, response.id);
      navigate(`/audits/${response?.id}`);
    } finally {
      setIsSaving(false);
    }
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
      {/* <Link to="..">← Go Back</Link>
       */}
      <h1 className="initial-focus-element">Audit Builder</h1>
      {auditCount >= AUDIT_SOFT_LIMIT && (
        <Card variant="short-error">
          <TbAlertTriangle className="icon-small" />
          <div className="font-small">
            <b>Large number of audits:</b> Your account has {auditCount.toLocaleString()} audits. Having a large number of audits may affect system performance.
          </div>
        </Card>
      )}
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
              <select
                id="scanFrequency"
                name="scanFrequency"
                className={styles["input-element"]}
                value={scanFrequency}
                onChange={(e) => setScanFrequency(e.target.value)}
              >
                <option>Manually</option>
                <option>Daily</option>
                <option>Weekly</option>
                <option>Monthly</option>
              </select>
            </StyledLabeledInput>
            {scanFrequency === "Daily" && pages.length >= URL_SOFT_LIMIT && (
              <p className="font-small" style={{ color: "#b45309", display: "flex", alignItems: "center", gap: "4px" }}>
                <TbAlertTriangle /> Daily scans for audits with {pages.length.toLocaleString()} URLs will use significant resources.
              </p>
            )}
          </Card>
          <Card variant="light">
            <h2>
              <TbMail className="icon-small" />
              Email Notifications:
            </h2>
            <AuditEmailSubscriptionInput
              initialValue={emailList}
              onValueChange={setEmailList}
            />
          </Card>
        </div>

        <Card variant="light" className={styles["pages-input-card"]}>
          <h2><FaListUl className="icon-small" /> Add URLs to Your Audit</h2>
          <Tabs.Root className="TabsRoot" defaultValue="remote-csv" onValueChange={() => { setRemoteCsvUrl(""); setPages([]); }}>
            <Tabs.List className={styles["pages-input-list"]} aria-label="Add URLs to Scan">
              <Tabs.Trigger value="remote-csv" asChild>
                <StyledButton
                  variant="tab-card-button"
                  label="Wordpress Integration"
                  icon={<FaCloudArrowDown />}
                  onClick={() => { }}
                />
              </Tabs.Trigger>
              
              <Tabs.Trigger value="crawl" asChild>
                <StyledButton
                  variant="tab-card-button"
                  label="Crawl Site"
                  icon={<FaSpider />}
                  onClick={() => { }}
                />
              </Tabs.Trigger>
              <Tabs.Trigger value="url" asChild>
                <StyledButton
                  variant="tab-card-button"
                  label="Add Manually"
                  icon={<FaFileCirclePlus />}
                  onClick={() => { }}
                />
              </Tabs.Trigger>
            </Tabs.List>
            <Tabs.Content value="url">
              <AuditPagesInput
                initialPages={pages}
                setParentPages={setPages}
                returnMutation={false}
                reverseLayout
              /></Tabs.Content>

            <Tabs.Content value="remote-csv">
              <AuditRemoteCsvInput
                pages={pages}
                setParentPages={setPages}
                csvUrl={remoteCsvUrl}
                setCsvUrl={setRemoteCsvUrl}
                validCsv={validRemoteCsv}
                setValidCsv={setValidRemoteCsv}
              />
            </Tabs.Content>

            <Tabs.Content value="crawl">
              <AuditCrawlInput
                pages={pages}
                setParentPages={setPages}
              />
            </Tabs.Content>
          </Tabs.Root>


        </Card>
        <div className={styles["action-buttons"]}>
          <StyledButton
            icon={<LuClipboardCheck />}
            onClick={saveAudit}
            label="Save Audit"
            disabled={pages.length < 1 || !auditNameValid || isSaving || isSavingAndRunning}
            loading={isSaving}
          />
          <StyledButton
            icon={<LuClipboardPaste />}
            onClick={saveAndRunAudit}
            variant="dark"
            label="Save & Run Audit"
            disabled={pages.length < 1 || !auditNameValid || isSaving || isSavingAndRunning}
            loading={isSavingAndRunning}
          />
        </div>
      </form>
    </div>
  );
};
