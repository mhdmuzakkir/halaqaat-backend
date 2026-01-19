const PDFDocument = require('pdfkit');
const fs = require('fs');
const path = require('path');
const Exam = require('../models/Exam');
const ExamMarks = require('../models/ExamMarks');
const Halqa = require('../models/Halqa');
const Student = require('../models/Student');
const User = require('../models/User');

// Register Urdu font for RTL text
const doc = new PDFDocument();

class PDFService {
  
  // Helper: Register fonts (Urdu support)
  registerFonts(doc) {
    // Try to register Urdu-supporting font
    try {
      doc.registerFont('Urdu', './fonts/urdu-font.ttf');
      doc.registerFont('UrduBold', './fonts/urdu-bold.ttf');
    } catch (err) {
      console.log('Urdu fonts not found, using default');
      // Fallback to default
    }
  }

  // Generate Kashf Imtehaan PDF
  async generateKashfImtehaan(examId, outputPath = null) {
    try {
      const exam = await Exam.findById(examId)
        .populate('halqaId')
        .populate('mumtaheenId', 'name');

      if (!exam) {
        throw new Error('Exam not found');
      }

      const halqa = await Halqa.findById(exam.halqaId)
        .populate('ustaazId', 'name');

      const examMarks = await ExamMarks.find({ examId })
        .populate('studentId');

      // Create PDF document
      const doc = new PDFDocument({
        size: 'A4',
        margin: 40
      });

      // Set RTL layout
      doc.rect(0, 0, doc.page.width, doc.page.height).stroke();

      // Title and Header Info (RTL Layout)
      const pageWidth = doc.page.width;
      const rightMargin = 40;
      const maxWidth = pageWidth - 80;

      // Title
      doc.fontSize(16).font('Helvetica-Bold').text('كشف امتحان', {
        align: 'right',
        width: maxWidth
      });

      // Exam Details (Right-aligned for RTL)
      doc.fontSize(11).font('Helvetica');
      
      const headerData = [
        { label: 'حلقہ کا نام:', value: halqa.name || 'N/A' },
        { label: 'استاذ کا نام:', value: halqa.ustaazId?.name || 'N/A' },
        { label: 'ممتحن کا نام:', value: exam.mumtaheenId?.name || 'Pending' },
        { label: 'تاریخ:', value: exam.examDate ? new Date(exam.examDate).toLocaleDateString('ur-PK') : 'N/A' }
      ];

      let yPosition = doc.y + 20;

      for (let item of headerData) {
        doc.fontSize(10).text(`${item.label} ${item.value}`, rightMargin, yPosition, {
          align: 'right'
        });
        yPosition += 15;
      }

      doc.moveTo(rightMargin, yPosition).lineTo(pageWidth - rightMargin, yPosition).stroke();

      // Student Details Table Header
      yPosition += 20;

      const tableHeaders = ['کل', 'سلوک', 'تک', 'سے', 'شعبہ', 'نام و نسب'];
      const colWidths = [35, 40, 40, 40, 50, 100];
      let xPos = pageWidth - rightMargin - 10;

      doc.fontSize(9).font('Helvetica-Bold');

      for (let i = 0; i < tableHeaders.length; i++) {
        xPos -= colWidths[i];
        doc.text(tableHeaders[i], xPos, yPosition, {
          width: colWidths[i],
          align: 'center'
        });
      }

      doc.moveTo(rightMargin, yPosition + 12).lineTo(pageWidth - rightMargin, yPosition + 12).stroke();

      // Student Data Rows
      yPosition += 20;
      doc.fontSize(8).font('Helvetica');

      for (let mark of examMarks) {
        if (yPosition > doc.page.height - 60) {
          doc.addPage();
          yPosition = 40;
        }

        const student = mark.studentId;
        const rowData = [
          mark.totalMarks || '',
          mark.sulookNumber || '',
          mark.toPage || '',
          mark.fromPage || '',
          mark.shuba || '',
          `${student.name || ''} / ${student.fatherName || ''}`
        ];

        xPos = pageWidth - rightMargin - 10;

        for (let i = 0; i < rowData.length; i++) {
          xPos -= colWidths[i];
          doc.text(String(rowData[i]), xPos, yPosition, {
            width: colWidths[i],
            align: i === 5 ? 'right' : 'center'
          });
        }

        yPosition += 15;
      }

      // Marks Table Header
      yPosition += 30;

      const marksHeaders = ['کل', 'اضافی', 'سلوک', 'اضافات', 'تجوید', 'حسن صوت', 'شماریات'];
      const marksColWidths = [35, 40, 40, 40, 40, 50, 60];

      doc.fontSize(9).font('Helvetica-Bold');
      xPos = pageWidth - rightMargin - 10;

      for (let i = 0; i < marksHeaders.length; i++) {
        xPos -= marksColWidths[i];
        doc.text(marksHeaders[i], xPos, yPosition, {
          width: marksColWidths[i],
          align: 'center'
        });
      }

      doc.moveTo(rightMargin, yPosition + 12).lineTo(pageWidth - rightMargin, yPosition + 12).stroke();

      // Marks Data Rows
      yPosition += 20;
      doc.fontSize(8).font('Helvetica');

      for (let mark of examMarks) {
        if (yPosition > doc.page.height - 40) {
          doc.addPage();
          yPosition = 40;
        }

        const rowData = [
          mark.totalMarks || '',
          mark.extraMarks || 0,
          mark.sulookMarks || 0,
          mark.izaafaat || 0,
          mark.tajweed || 0,
          mark.husnSawt || 0,
          mark.subjectMarks || ''
        ];

        xPos = pageWidth - rightMargin - 10;

        for (let i = 0; i < rowData.length; i++) {
          xPos -= marksColWidths[i];
          doc.text(String(rowData[i]), xPos, yPosition, {
            width: marksColWidths[i],
            align: 'center'
          });
        }

        yPosition += 15;
      }

      // Save PDF
      const fileName = `kashf_halqa_${halqa.halqaNumber}_${Date.now()}.pdf`;
      const filePath = outputPath || path.join(__dirname, '../pdfs', fileName);

      return new Promise((resolve, reject) => {
        doc.pipe(fs.createWriteStream(filePath));
        doc.end();
        doc.on('finish', () => {
          // Update exam record with PDF path
          Exam.findByIdAndUpdate(examId, {
            kashfPath: filePath,
            kashfGeneratedAt: new Date()
          }).catch(err => console.error('Error updating exam:', err));

          resolve({
            success: true,
            filePath,
            fileName
          });
        });
        doc.on('error', reject);
      });

    } catch (error) {
      throw new Error(`PDF Generation Error: ${error.message}`);
    }
  }

  // Generate Individual Student Result PDF
  async generateIndividualResultPDF(studentId, examId, outputPath = null) {
    try {
      const examMark = await ExamMarks.findOne({ studentId, examId })
        .populate('studentId')
        .populate('examId');

      if (!examMark) {
        throw new Error('Exam marks not found');
      }

      const student = examMark.studentId;
      const exam = examMark.examId;
      const halqa = await Halqa.findById(exam.halqaId).populate('ustaazId');

      const doc = new PDFDocument({
        size: 'A4',
        margin: 40
      });

      const pageWidth = doc.page.width;
      const rightMargin = 40;
      const maxWidth = pageWidth - 80;

      // Title
      doc.fontSize(16).font('Helvetica-Bold').text('نتیجہ کی رپورٹ', {
        align: 'center'
      });

      // Student Information Section
      doc.fontSize(11).font('Helvetica-Bold').text('طالب علم کی معلومات', {
        align: 'right'
      });

      doc.fontSize(10).font('Helvetica');
      let yPos = doc.y + 10;

      const studentInfo = [
        { label: 'نام:', value: student.name },
        { label: 'والد کا نام:', value: student.fatherName },
        { label: 'جنس:', value: student.gender === 'M' ? 'لڑکا' : 'لڑکی' },
        { label: 'شعبہ:', value: examMark.shuba },
        { label: 'حلقہ:', value: halqa.name },
        { label: 'استاذ:', value: halqa.ustaazId?.name || 'N/A' }
      ];

      for (let info of studentInfo) {
        doc.text(`${info.label} ${info.value}`, rightMargin, yPos, {
          align: 'right'
        });
        yPos += 15;
      }

      // Exam Details
      doc.fontSize(11).font('Helvetica-Bold').text('امتحانی نتائج', {
        align: 'right'
      });

      yPos = doc.y + 10;

      // Marks Table
      const marksData = [
        ['شماریات / Marks', examMark.subjectMarks || 0],
        ['حسن صوت / Husn Sawt', examMark.husnSawt || 0],
        ['تجوید / Tajweed', examMark.tajweed || 0],
        ['اضافات / Izaafaat', examMark.izaafaat || 0],
        ['سلوک / Sulook', examMark.sulookMarks || 0],
        ['اضافی / Extra', examMark.extraMarks || 0]
      ];

      doc.fontSize(9).font('Helvetica');

      for (let i = 0; i < marksData.length; i++) {
        doc.text(`${marksData[i][0]}: ${marksData[i][1]}`, rightMargin, yPos, {
          align: 'right'
        });
        yPos += 15;
      }

      // Total and Grade
      yPos += 10;
      doc.fontSize(12).font('Helvetica-Bold');
      doc.text(`کل نمبرات: ${examMark.totalMarks || 0}`, rightMargin, yPos, {
        align: 'right'
      });

      yPos += 20;
      doc.fontSize(11);
      doc.text(`تقدیر (Grade): ${examMark.grade || 'N/A'}`, rightMargin, yPos, {
        align: 'right'
      });

      // Status
      yPos += 30;
      const statusText = examMark.mumayyizStatus ? 'معیاز (No Absence)' : 'عام';
      doc.text(`حالت: ${statusText}`, rightMargin, yPos, {
        align: 'right'
      });

      // Save PDF
      const fileName = `result_${student.name}_${Date.now()}.pdf`;
      const filePath = outputPath || path.join(__dirname, '../pdfs', fileName);

      return new Promise((resolve, reject) => {
        doc.pipe(fs.createWriteStream(filePath));
        doc.end();
        doc.on('finish', () => {
          resolve({
            success: true,
            filePath,
            fileName
          });
        });
        doc.on('error', reject);
      });

    } catch (error) {
      throw new Error(`Individual PDF Generation Error: ${error.message}`);
    }
  }
}

module.exports = new PDFService();