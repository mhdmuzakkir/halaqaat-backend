const ExamMarks = require('../models/ExamMarks');
const Student = require('../models/Student');
const Halqa = require('../models/Halqa');
const PDFService = require('./pdfService');

class ReportService {

  // Get Mumayyiz Talaba (Boys with zero absences)
  async getMumayyizTalaba(academicYear = '2025-2026') {
    try {
      return await ExamMarks.find({
        mumayyizStatus: true,
        academicYear
      })
        .populate({
          path: 'studentId',
          match: { gender: 'M' },
          select: 'name fatherName halqaId'
        })
        .populate({
          path: 'halqaId',
          select: 'name'
        })
        .sort({ 'studentId.name': 1 });
    } catch (error) {
      throw new Error('Error fetching Mumayyiz Talaba: ' + error.message);
    }
  }

  // Get Mumtaz Talaba (Boys with marks >= 85)
  async getMumtazTalaba(academicYear = '2025-2026') {
    try {
      return await ExamMarks.find({
        totalMarks: { $gte: 85 },
        academicYear
      })
        .populate({
          path: 'studentId',
          match: { gender: 'M' },
          select: 'name fatherName halqaId'
        })
        .populate({
          path: 'halqaId',
          select: 'name'
        })
        .sort({ totalMarks: -1 });
    } catch (error) {
      throw new Error('Error fetching Mumtaz Talaba: ' + error.message);
    }
  }

  // Get Mumayyiz Talibaat (Girls with zero absences)
  async getMumayyizTalibaat(academicYear = '2025-2026') {
    try {
      return await ExamMarks.find({
        mumayyizStatus: true,
        academicYear
      })
        .populate({
          path: 'studentId',
          match: { gender: 'F' },
          select: 'name fatherName halqaId'
        })
        .populate({
          path: 'halqaId',
          select: 'name'
        })
        .sort({ 'studentId.name': 1 });
    } catch (error) {
      throw new Error('Error fetching Mumayyiz Talibaat: ' + error.message);
    }
  }

  // Get Mumtaz Talibaat (Girls with marks >= 85)
  async getMumtazTalibaat(academicYear = '2025-2026') {
    try {
      return await ExamMarks.find({
        totalMarks: { $gte: 85 },
        academicYear
      })
        .populate({
          path: 'studentId',
          match: { gender: 'F' },
          select: 'name fatherName halqaId'
        })
        .populate({
          path: 'halqaId',
          select: 'name'
        })
        .sort({ totalMarks: -1 });
    } catch (error) {
      throw new Error('Error fetching Mumtaz Talibaat: ' + error.message);
    }
  }

  // Get halqa performance report
  async getHalqaPerformanceReport(halqaId, academicYear = '2025-2026') {
    try {
      const halqa = await Halqa.findById(halqaId);
      
      const marks = await ExamMarks.find({
        halqaId,
        academicYear
      })
        .populate('studentId', 'name fatherName gender')
        .sort({ totalMarks: -1 });

      const totalStudents = marks.length;
      const passedStudents = marks.filter(m => m.totalMarks >= 50).length;
      const excellentStudents = marks.filter(m => m.totalMarks >= 85).length;
      const averageMarks = marks.reduce((sum, m) => sum + (m.totalMarks || 0), 0) / totalStudents;

      return {
        halqaName: halqa.name,
        gender: halqa.gender,
        totalStudents,
        passedStudents,
        passPercentage: ((passedStudents / totalStudents) * 100).toFixed(2),
        excellentStudents,
        excellentPercentage: ((excellentStudents / totalStudents) * 100).toFixed(2),
        averageMarks: averageMarks.toFixed(2),
        marks
      };
    } catch (error) {
      throw new Error('Error generating halqa report: ' + error.message);
    }
  }
}

module.exports = new ReportService();