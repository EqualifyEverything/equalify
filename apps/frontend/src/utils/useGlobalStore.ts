import { BlockersTableColumnToggle } from "#src/components/BlockersTableColumnToggle.tsx";
import { OnChangeFn, Updater, VisibilityState } from "@tanstack/react-table";
import { toast } from "sonner";
import { create } from "zustand";
import { persist } from "zustand/middleware";


interface EqualifyState {
  loading: boolean | string;
  setLoading: (val: string | boolean) => void;
  darkMode: boolean;
  setDarkMode: (val: boolean) => void;
  authenticated: boolean;
  setAuthenticated: (val: boolean) => void;
  ssoAuthenticated: boolean;
  setSsoAuthenticated: (val: boolean) => void;
  // audits listing screen
  auditsTableView: string;
  setAuditsTableView: (val: string) => void;
  auditsTableCreatedByView: string;
  setAuditsTableCreatedByView: (val:string) => void;
  // blockers table
  blockersTableView: string;
  setBlockersTableView: (val: string) => void;
  blockerTableColumnVisibility: VisibilityState;
  setBlockerTableColumnVisibility: OnChangeFn<VisibilityState>;
  // screen reader announcer
  announceMessage: string;
  setAnnounceMessage: (
    val: string,
    style?: "normal" | "success" | "error",
    screenReaderOnly?: boolean,
  ) => void;
}

export const useGlobalStore = create<EqualifyState>()(
  persist(
    (set) => ({
      loading: false,
      setLoading: (val) => set(() => ({ loading: val })),
      darkMode: false,
      setDarkMode: (val) => set(() => ({ darkMode: val })),
      auditsTableView: "cards",
      setAuditsTableView: (val) => set(() => ({ auditsTableView: val })),
      auditsTableCreatedByView: "user",
      setAuditsTableCreatedByView: (val) => set(() => ({auditsTableCreatedByView : val})),
      blockersTableView: "summary",
      setBlockersTableView: (val) => set(() => ({ blockersTableView: val })),
      authenticated: false,
      setAuthenticated: (val) => set(() => ({ authenticated: val })),
      ssoAuthenticated: false,
      setSsoAuthenticated: (val) => set(() => ({ ssoAuthenticated: val })),
      announceMessage: "",
      setAnnounceMessage: (
        val,
        style = "normal",
        setScreenReaderOnly = false,
      ) => {
        set(() => ({ announceMessage: val }));
        if (!setScreenReaderOnly) {
          switch (style) {
            case "success":
              toast.success(val);
              return;
            case "error":
              toast.error(val);
              return;
            default:
              toast(val);
          }
        }
      },
      blockerTableColumnVisibility: {
        // defaults for column visibility
        categories: false,
        tags: false,
        id: false, // this is the "ignored" column
        content: true,
        messages: true,
        url: true,
        type: true,
      },
      setBlockerTableColumnVisibility: (updater) =>
        set((state) => ({
          blockerTableColumnVisibility:
            typeof updater === "function"
              ? updater(state.blockerTableColumnVisibility)
              : updater,
        })),
    }),
    {
      name: "equalify-storage",
      partialize: (state) => ({
        darkMode: state.darkMode,
        authenticated: state.authenticated,
        ssoAuthenticated: state.ssoAuthenticated,
      }),
    },
  ),
);
