import * as XLSX from 'xlsx';
import { LocaleType } from '@univerjs/presets';

/**
 * Converts an XLSX file to Univer's IWorkbookData format
 */
export async function importXLSXToUniver(file: File) {
  try {
    // Read the file as ArrayBuffer
    const arrayBuffer = await file.arrayBuffer();

    // Parse the XLSX file
    const workbook = XLSX.read(arrayBuffer, { type: 'array' });

    // Convert to Univer format
    const univerWorkbook = convertWorkbookToUniver(workbook, file.name);

    return univerWorkbook;
  } catch (error) {
    console.error('Error importing XLSX file:', error);
    throw new Error('Failed to import XLSX file. Please ensure the file is a valid Excel file.');
  }
}

/**
 * Converts XLSX workbook to Univer IWorkbookData format
 */
function convertWorkbookToUniver(workbook: XLSX.WorkBook, fileName: string) {
  const sheets: any = {};
  const sheetOrder: string[] = [];

  // Process each sheet
  workbook.SheetNames.forEach((sheetName, index) => {
    const worksheet = workbook.Sheets[sheetName];
    const sheetId = `sheet-${index + 1}`;

    sheetOrder.push(sheetId);

    // Convert worksheet to Univer format
    sheets[sheetId] = {
      id: sheetId,
      name: sheetName,
      cellData: convertWorksheetToCellData(worksheet),
    };
  });

  // Create Univer workbook data
  return {
    id: `workbook-${Date.now()}`,
    locale: LocaleType.EN_US,
    name: fileName.replace('.xlsx', '').replace('.xls', ''),
    sheetOrder,
    appVersion: '3.0.0-alpha',
    sheets,
  };
}

/**
 * Converts XLSX worksheet to Univer cellData format
 */
function convertWorksheetToCellData(worksheet: XLSX.WorkSheet) {
  const cellData: any = {};
  const range = XLSX.utils.decode_range(worksheet['!ref'] || 'A1');

  // Iterate through all cells in the range
  for (let row = range.s.r; row <= range.e.r; row++) {
    for (let col = range.s.c; col <= range.e.c; col++) {
      const cellAddress = XLSX.utils.encode_cell({ r: row, c: col });
      const cell = worksheet[cellAddress];

      if (!cell) continue;

      // Initialize row if it doesn't exist
      if (!cellData[row]) {
        cellData[row] = {};
      }

      // Convert cell value and format
      const univerCell: any = {
        v: getCellValue(cell),
      };

      // Add cell type
      if (cell.t === 'n') {
        univerCell.t = 2; // Number type in Univer
      } else if (cell.t === 's') {
        univerCell.t = 1; // String type in Univer
      } else if (cell.t === 'b') {
        univerCell.t = 4; // Boolean type in Univer
      }

      // Add formatting if available
      if (cell.s) {
        univerCell.s = convertCellStyle(cell.s);
      }

      cellData[row][col] = univerCell;
    }
  }

  return cellData;
}

/**
 * Gets the cell value from XLSX cell
 */
function getCellValue(cell: XLSX.CellObject): any {
  if (cell.t === 'n') {
    return cell.v; // Number
  } else if (cell.t === 's') {
    return cell.v; // String
  } else if (cell.t === 'b') {
    return cell.v; // Boolean
  } else if (cell.t === 'd') {
    return cell.v; // Date
  } else if (cell.w) {
    return cell.w; // Formatted value
  }
  return cell.v;
}

/**
 * Converts XLSX cell style to Univer style format
 * Note: This is a simplified version. Full style conversion would be more complex.
 */
function convertCellStyle(xlsxStyle: any): any {
  const univerStyle: any = {};

  // This is a simplified version
  // In a full implementation, you would map all XLSX styles to Univer styles
  // For now, we'll just return basic style info

  return univerStyle;
}

/**
 * Exports Univer workbook data to XLSX format
 */
export function exportUniverToXLSX(workbookData: any, filename: string = 'export.xlsx') {
  try {
    const workbook = XLSX.utils.book_new();

    // Process each sheet
    workbookData.sheetOrder.forEach((sheetId: string) => {
      const sheet = workbookData.sheets[sheetId];
      const worksheet = convertUniverSheetToWorksheet(sheet);

      XLSX.utils.book_append_sheet(workbook, worksheet, sheet.name);
    });

    // Generate XLSX file and trigger download
    XLSX.writeFile(workbook, filename);
  } catch (error) {
    console.error('Error exporting to XLSX:', error);
    throw new Error('Failed to export to XLSX file.');
  }
}

/**
 * Converts Univer sheet to XLSX worksheet
 */
function convertUniverSheetToWorksheet(sheet: any): XLSX.WorkSheet {
  const worksheet: XLSX.WorkSheet = {};
  const cellData = sheet.cellData || {};

  let maxRow = 0;
  let maxCol = 0;

  // Convert each cell
  Object.keys(cellData).forEach((rowStr) => {
    const row = parseInt(rowStr);
    maxRow = Math.max(maxRow, row);

    Object.keys(cellData[row]).forEach((colStr) => {
      const col = parseInt(colStr);
      maxCol = Math.max(maxCol, col);

      const univerCell = cellData[row][col];
      const cellAddress = XLSX.utils.encode_cell({ r: row, c: col });

      // Create XLSX cell
      const xlsxCell: XLSX.CellObject = {
        v: univerCell.v,
        t: getXLSXCellType(univerCell.t),
      };

      worksheet[cellAddress] = xlsxCell;
    });
  });

  // Set worksheet range
  worksheet['!ref'] = XLSX.utils.encode_range({
    s: { r: 0, c: 0 },
    e: { r: maxRow, c: maxCol },
  });

  return worksheet;
}

/**
 * Maps Univer cell type to XLSX cell type
 */
function getXLSXCellType(univerType: number): XLSX.ExcelDataType {
  switch (univerType) {
    case 1:
      return 's'; // String
    case 2:
      return 'n'; // Number
    case 4:
      return 'b'; // Boolean
    default:
      return 's'; // Default to string
  }
}
