# Scoring vs Switch Cases - ความต่างและการใช้งานจริง

## 🎯 **Switch Cases** - การตัดสินใจแบบเลือกหนึ่งเดียว

### ✨ **ลักษณะ:**
- **เลือกได้เพียง 1 ผลลัพธ์** จากหลายตัวเลือก
- เหมือน `if-else if-else` ใน programming
- **หยุดทันที** เมื่อเจอเงื่อนไขแรกที่ตรง
- Return ค่าเดียว (string, number, boolean)

### 🏦 **กรณีใช้งานจริง:**

#### 1. **ระบบอนุมัติสินเชื่อ**
```json
{
  "id": "loan_decision",
  "switch": "credit_score",
  "when": [
    {
      "if": {"op": ">=", "value": 80},
      "result": "อนุมัติทันที",
      "set_vars": {"$interest_rate": 3.5, "$max_amount": 5000000}
    },
    {
      "if": {"op": ">=", "value": 60},
      "result": "อนุมัติแบบมีเงื่อนไข", 
      "set_vars": {"$interest_rate": 5.0, "$max_amount": 2000000}
    },
    {
      "if": {"op": ">=", "value": 40},
      "result": "ต้องมีผู้ค้ำประกัน",
      "set_vars": {"$interest_rate": 7.0, "$max_amount": 500000}
    }
  ],
  "default": "ปฏิเสธ"
}
```

#### 2. **ระบบกำหนดราคาสินค้า**
```json
{
  "id": "pricing_tier",
  "switch": "customer_type",
  "when": [
    {"if": {"op": "==", "value": "VIP"}, "result": "ลด 30%"},
    {"if": {"op": "==", "value": "Gold"}, "result": "ลด 20%"},
    {"if": {"op": "==", "value": "Silver"}, "result": "ลด 10%"}
  ],
  "default": "ราคาปกติ"
}
```

#### 3. **ระบบจัดส่งสินค้า**
```json
{
  "id": "shipping_method",
  "switch": "order_total",
  "when": [
    {"if": {"op": ">=", "value": 2000}, "result": "ส่งฟรี Express"},
    {"if": {"op": ">=", "value": 1000}, "result": "ส่งฟรี ปกติ"},
    {"if": {"op": ">=", "value": 500}, "result": "ค่าส่ง 50 บาท"}
  ],
  "default": "ค่าส่ง 100 บาท"
}
```

---

## 🏆 **Scoring** - การให้คะแนนแบบสะสม

### ✨ **ลักษณะ:**
- **คำนวณคะแนนจากหลายปัจจัย** รวมกัน
- แต่ละปัจจัยมีน้ำหนักแยกกัน
- **รวมคะแนนทั้งหมด** เป็นผลลัพธ์สุดท้าย
- Return ตัวเลข (score) + ข้อมูลเพิ่มเติม

### 🏦 **กรณีใช้งานจริง:**

#### 1. **ระบบประเมินความเสี่ยงสินเชื่อ**
```json
{
  "id": "credit_risk_score",
  "rules": [
    {
      "var": "annual_income",
      "ranges": [
        {"if": {"op": ">=", "value": 1000000}, "result": 40},
        {"if": {"op": ">=", "value": 500000}, "result": 25},
        {"if": {"op": ">=", "value": 300000}, "result": 15}
      ]
    },
    {
      "var": "employment_years",
      "ranges": [
        {"if": {"op": ">=", "value": 5}, "result": 20},
        {"if": {"op": ">=", "value": 2}, "result": 15},
        {"if": {"op": ">=", "value": 1}, "result": 10}
      ]
    },
    {
      "var": "debt_to_income_ratio",
      "ranges": [
        {"if": {"op": "<=", "value": 30}, "result": 25},
        {"if": {"op": "<=", "value": 50}, "result": 15},
        {"if": {"op": "<=", "value": 70}, "result": 5}
      ]
    },
    {
      "var": "has_collateral",
      "if": {"op": "==", "value": true},
      "result": 15
    }
  ]
}
```
**ผลลัพธ์:** รวมคะแนนได้ 0-100 แล้วนำไปตัดสินต่อ

#### 2. **ระบบประเมินพนักงาน**
```json
{
  "id": "employee_performance",
  "rules": [
    {
      "var": "sales_target_achievement",
      "ranges": [
        {"if": {"op": ">=", "value": 120}, "result": 30},
        {"if": {"op": ">=", "value": 100}, "result": 25},
        {"if": {"op": ">=", "value": 80}, "result": 15}
      ]
    },
    {
      "var": "customer_satisfaction",
      "ranges": [
        {"if": {"op": ">=", "value": 4.5}, "result": 25},
        {"if": {"op": ">=", "value": 4.0}, "result": 20},
        {"if": {"op": ">=", "value": 3.5}, "result": 10}
      ]
    },
    {
      "var": "teamwork_score",
      "ranges": [
        {"if": {"op": ">=", "value": 90}, "result": 20},
        {"if": {"op": ">=", "value": 80}, "result": 15},
        {"if": {"op": ">=", "value": 70}, "result": 10}
      ]
    },
    {
      "var": "training_completed",
      "if": {"op": "==", "value": true},
      "result": 25
    }
  ]
}
```

#### 3. **ระบบจัดอันดับสินค้า**
```json
{
  "id": "product_ranking",
  "rules": [
    {
      "var": "review_average",
      "ranges": [
        {"if": {"op": ">=", "value": 4.5}, "result": 35},
        {"if": {"op": ">=", "value": 4.0}, "result": 25},
        {"if": {"op": ">=", "value": 3.5}, "result": 15}
      ]
    },
    {
      "var": "sales_volume",
      "ranges": [
        {"if": {"op": ">=", "value": 1000}, "result": 30},
        {"if": {"op": ">=", "value": 500}, "result": 20},
        {"if": {"op": ">=", "value": 100}, "result": 10}
      ]
    },
    {
      "var": "return_rate",
      "ranges": [
        {"if": {"op": "<=", "value": 2}, "result": 20},
        {"if": {"op": "<=", "value": 5}, "result": 15},
        {"if": {"op": "<=", "value": 10}, "result": 5}
      ]
    },
    {
      "var": "is_premium_brand",
      "if": {"op": "==", "value": true},
      "result": 15
    }
  ]
}
```

---

## 🎪 **Multi-dimensional Scoring** - การให้คะแนนแบบหลายมิติ

### ✨ **ลักษณะ:**
- **ตัดสินใจตามปัจจัยหลายตัวพร้อมกัน**
- เหมือน Matrix หรือ Decision Tree
- ซับซ้อนกว่า แต่แม่นยำกว่า

### 🏦 **กรณีใช้งานจริง:**

#### **ระบบประเมินความเสี่ยงการลงทุน**
```json
{
  "id": "investment_risk",
  "scoring": {
    "ifs": {
      "vars": ["age", "income", "investment_experience"],
      "tree": [
        {
          "if": {"op": "between", "value": [25, 40]},
          "ranges": [
            {
              "if": {"op": ">=", "value": 1000000},
              "ranges": [
                {
                  "if": {"op": ">=", "value": 5},
                  "score": 95,
                  "risk_level": "สูง",
                  "recommended_portfolio": "60% หุ้น, 30% กองทุน, 10% พันธบัตร"
                },
                {
                  "if": {"op": ">=", "value": 2},
                  "score": 75,
                  "risk_level": "กลาง-สูง",
                  "recommended_portfolio": "40% หุ้น, 40% กองทุน, 20% พันธบัตร"
                }
              ]
            }
          ]
        }
      ]
    }
  }
}
```

---

## 📊 **สรุปความต่าง**

| ลักษณะ | Switch Cases | Scoring | Multi-dimensional |
|--------|-------------|---------|------------------|
| **วัตถุประสงค์** | เลือกผลลัพธ์เดียว | รวมคะแนนจากหลายปัจจัย | ตัดสินใจแบบ Matrix |
| **ผลลัพธ์** | 1 ค่าเดียว | คะแนนรวม | คะแนน + ข้อมูลเพิ่มเติม |
| **การทำงาน** | หยุดเมื่อเจอเงื่อนไขแรก | ประเมินทุกปัจจัย | ประเมินแบบหลายมิติ |
| **ความซับซ้อน** | ง่าย | ปานกลาง | ซับซ้อน |
| **การใช้งาน** | การจัดหมวดหมู่ | การให้คะแนน | การตัดสินใจแบบซับซ้อน |

## 🎯 **เลือกใช้เมื่อไหร่?**

### ใช้ **Switch Cases** เมื่อ:
- ต้องการ**เลือกหนึ่งเดียว**จากหลายตัวเลือก
- มีเงื่อนไขที่ชัดเจน แยกขาดกัน
- ผลลัพธ์เป็น category หรือ action

### ใช้ **Scoring** เมื่อ:
- ต้องการ**ประเมินจากหลายปัจจัย**
- แต่ละปัจจัยมีน้ำหนักต่างกัน
- ผลลัพธ์เป็นคะแนนรวม

### ใช้ **Multi-dimensional** เมื่อ:
- ปัจจัยมี**ความสัมพันธ์กัน**
- ต้องการความแม่นยำสูง
- การตัดสินใจซับซ้อน

---

## 🔄 **ตัวอย่างการใช้ร่วมกัน**

```json
{
  "formulas": [
    {
      "id": "credit_score",
      "rules": [...],  // คำนวณคะแนนรวม
      "comment": "ใช้ Scoring หาคะแนน"
    },
    {
      "id": "final_decision", 
      "switch": "credit_score",  // นำคะแนนมาตัดสินใจ
      "when": [
        {"if": {"op": ">=", "value": 80}, "result": "อนุมัติ"},
        {"if": {"op": ">=", "value": 60}, "result": "พิจารณา"}
      ],
      "default": "ปฏิเสธ",
      "comment": "ใช้ Switch ตัดสินใจสุดท้าย"
    }
  ]
}
```

**สรุป:** Scoring หาคะแนน → Switch ตัดสินใจ = ระบบที่สมบูรณ์!