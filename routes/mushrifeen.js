const express = require('express');
const Halqa = require('../models/Halqa');
const Student = require('../models/Student');
const AuditLog = require('../models/AuditLog');
const { protect, authorize } = require('../middleware/auth');

const router = express.Router();

// Middleware to verify mushrifeen
router.use(protect);
router.use(authorize('mushrifeen'));

// @route   GET /api/mushrifeen/halqas
// @desc    Get halqas assigned to this mushrifeen
router.get('/halqas', async (req, res) => {
  try {
    const halqas = await Halqa.find({ mushrieenId: req.user.id });

    res.status(200).json({ success: true, data: halqas });
  } catch (error) {
    res.status(500).json({ message: error.message });
  }
});

// @route   GET /api/mushrifeen/halqa/:halqaId/students
// @desc    Get students in halqa
router.get('/halqa/:halqaId/students', async (req, res) => {
  try {
    const students = await Student.find({ halqaId: req.params.halqaId });

    res.status(200).json({ success: true, data: students });
  } catch (error) {
    res.status(500).json({ message: error.message });
  }
});

// @route   POST /api/mushrifeen/student/transfer
// @desc    Transfer student from one halqa to another
router.post('/student/transfer', async (req, res) => {
  try {
    const { studentId, newHalqaId } = req.body;

    const student = await Student.findByIdAndUpdate(
      studentId,
      { halqaId: newHalqaId },
      { new: true }
    );

    await AuditLog.create({
      userId: req.user.id,
      action: 'Transferred student',
      studentId,
      details: { newHalqaId }
    });

    res.status(200).json({ success: true, data: student });
  } catch (error) {
    res.status(500).json({ message: error.message });
  }
});

// @route   POST /api/mushrifeen/student/add
// @desc    Add new student to halqa
router.post('/student/add', async (req, res) => {
  try {
    const { name, fatherName, gender, halqaId, shuba } = req.body;

    const student = new Student({
      name,
      fatherName,
      gender,
      halqaId,
      shuba
    });

    await student.save();

    await AuditLog.create({
      userId: req.user.id,
      action: 'Added new student',
      studentId: student._id,
      halqaId,
      details: { name, shuba }
    });

    res.status(201).json({ success: true, data: student });
  } catch (error) {
    res.status(500).json({ message: error.message });
  }
});

module.exports = router;