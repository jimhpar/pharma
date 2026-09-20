param(
    [Parameter(Mandatory = $true)]
    [string] $PrinterName,

    [Parameter(Mandatory = $true)]
    [string] $FilePath
)

$source = @"
using System;
using System.IO;
using System.Runtime.InteropServices;

public class RawPrinterHelper
{
    [StructLayout(LayoutKind.Sequential, CharSet = CharSet.Ansi)]
    public class DOCINFOA
    {
        [MarshalAs(UnmanagedType.LPStr)]
        public string pDocName;
        [MarshalAs(UnmanagedType.LPStr)]
        public string pOutputFile;
        [MarshalAs(UnmanagedType.LPStr)]
        public string pDataType;
    }

    [DllImport("winspool.Drv", EntryPoint = "OpenPrinterA", SetLastError = true, CharSet = CharSet.Ansi, ExactSpelling = true, CallingConvention = CallingConvention.StdCall)]
    public static extern bool OpenPrinter(string szPrinter, out IntPtr hPrinter, IntPtr pd);

    [DllImport("winspool.Drv", EntryPoint = "ClosePrinter", SetLastError = true, ExactSpelling = true, CallingConvention = CallingConvention.StdCall)]
    public static extern bool ClosePrinter(IntPtr hPrinter);

    [DllImport("winspool.Drv", EntryPoint = "StartDocPrinterA", SetLastError = true, CharSet = CharSet.Ansi, ExactSpelling = true, CallingConvention = CallingConvention.StdCall)]
    public static extern bool StartDocPrinter(IntPtr hPrinter, Int32 level, [In, MarshalAs(UnmanagedType.LPStruct)] DOCINFOA di);

    [DllImport("winspool.Drv", EntryPoint = "EndDocPrinter", SetLastError = true, ExactSpelling = true, CallingConvention = CallingConvention.StdCall)]
    public static extern bool EndDocPrinter(IntPtr hPrinter);

    [DllImport("winspool.Drv", EntryPoint = "StartPagePrinter", SetLastError = true, ExactSpelling = true, CallingConvention = CallingConvention.StdCall)]
    public static extern bool StartPagePrinter(IntPtr hPrinter);

    [DllImport("winspool.Drv", EntryPoint = "EndPagePrinter", SetLastError = true, ExactSpelling = true, CallingConvention = CallingConvention.StdCall)]
    public static extern bool EndPagePrinter(IntPtr hPrinter);

    [DllImport("winspool.Drv", EntryPoint = "WritePrinter", SetLastError = true, ExactSpelling = true, CallingConvention = CallingConvention.StdCall)]
    public static extern bool WritePrinter(IntPtr hPrinter, IntPtr pBytes, Int32 dwCount, out Int32 dwWritten);

    public static void SendFileToPrinter(string printerName, string filePath)
    {
        byte[] bytes = File.ReadAllBytes(filePath);
        IntPtr pUnmanagedBytes = Marshal.AllocCoTaskMem(bytes.Length);
        Marshal.Copy(bytes, 0, pUnmanagedBytes, bytes.Length);

        IntPtr hPrinter;
        DOCINFOA di = new DOCINFOA();
        di.pDocName = "POS Thermal Receipt";
        di.pDataType = "RAW";

        try
        {
            if (!OpenPrinter(printerName.Normalize(), out hPrinter, IntPtr.Zero)) {
                throw new Exception("OpenPrinter failed: " + Marshal.GetLastWin32Error());
            }

            try
            {
                if (!StartDocPrinter(hPrinter, 1, di)) {
                    throw new Exception("StartDocPrinter failed: " + Marshal.GetLastWin32Error());
                }

                try
                {
                    if (!StartPagePrinter(hPrinter)) {
                        throw new Exception("StartPagePrinter failed: " + Marshal.GetLastWin32Error());
                    }

                    int written;
                    if (!WritePrinter(hPrinter, pUnmanagedBytes, bytes.Length, out written)) {
                        throw new Exception("WritePrinter failed: " + Marshal.GetLastWin32Error());
                    }

                    EndPagePrinter(hPrinter);
                }
                finally
                {
                    EndDocPrinter(hPrinter);
                }
            }
            finally
            {
                ClosePrinter(hPrinter);
            }
        }
        finally
        {
            Marshal.FreeCoTaskMem(pUnmanagedBytes);
        }
    }
}
"@

Add-Type -TypeDefinition $source
[RawPrinterHelper]::SendFileToPrinter($PrinterName, $FilePath)
