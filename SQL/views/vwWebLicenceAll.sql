USE [LGS_UAT]
GO

/****** Object:  View [dbo].[vwWebLicenceAll]    Script Date: 1/07/2022 11:52:57 AM ******/
SET ANSI_NULLS ON
GO

SET QUOTED_IDENTIFIER ON
GO




ALTER View [dbo].[vwWebLicenceAll]
as

	/*INSERT_SELECT_STATEMENT*/

SELECT 
	Licences.LIC_ID,LicenceNumbers.LN_LicenceNumber, LicenceNumbers.LN_ID, LicenceNumbers.LN_LC_ID, vwLiquorLicences.LD_AS_ID,
    PremisesNames.PN_Name, PremisesNames.PN_ID, LIC_PremisesAddress1, LIC_PremisesAddress2,
    LIC_PremisesTown, LIC_PremisesPostCode, LIC_PremisesState,
    LIC_PremisesPhone, LIC_PremisesFax, LPD_Address1, LPD_Address2,
    LPD_Town, LPD_State, LPD_PostCode,
    ISNULL(vwLiquorLicences.LD_Status,'') + ' ' + ISNULL(vwLicDetailsStatus.LV_Desc,'') as Liquor_Status, 
    vwLiquorLicences.LD_DateIssued as Liquor_DateIssued,
    vwLiquorLicences.LD_DateSurrendered as Liquor_Datesurrendered,
    vwLiquorLicences.LD_DateTransferred as Liquor_DateTransferred,
    vwLiquorLicences.LD_DateSuspFrom as Liquor_DateSuspFrom,
    vwLiquorLicences.LD_DateSuspTo as Liquor_DateSuspTo, LIC_TripCat,
    Liq_EN = CASE ISNULL(vwLiquorLicences.LD_ID,0) WHEN 0 THEN '' ELSE CASE ISNULL((SELECT count(*) FROM vwLiquorConsents WHERE LC_LD_ID =  vwLiquorLicences.LD_ID AND LV_Value like 'EN'),0) WHEN 0 THEN 'No' ELSE 'Yes' END END,
    Liq_ET = CASE ISNULL(vwLiquorLicences.LD_ID,0) WHEN 0 THEN '' ELSE CASE ISNULL((SELECT count(*) FROM vwLiquorConsents WHERE LC_LD_ID =  vwLiquorLicences.LD_ID AND LV_Value like 'ET'),0) WHEN 0 THEN 'No' ELSE 'Yes' END END,
    Liq_EX = CASE ISNULL(vwLiquorLicences.LD_ID,0) WHEN 0 THEN '' ELSE CASE ISNULL((SELECT count(*) FROM vwLiquorConsents WHERE LC_LD_ID =  vwLiquorLicences.LD_ID AND LV_Value like 'EX'),0) WHEN 0 THEN 'No' ELSE 'Yes' END END,
    Liq_C = CASE ISNULL(vwLiquorLicences.LD_ID,0) WHEN 0 THEN '' ELSE CASE ISNULL((SELECT count(*) FROM vwLiquorConsents WHERE LC_LD_ID =  vwLiquorLicences.LD_ID AND LV_Value like 'C'),0) WHEN 0 THEN 'No' ELSE 'Yes' END END,
	ISNULL(vwGamingLicences.LD_Status,'') + ' ' + ISNULL(vwGamDetailsStatus.LV_Desc,'') as Gaming_Status, 
    vwGamingLicences.LD_DateIssued as Gaming_DateIssued,
    vwGamingLicences.LD_DateSurrendered as Gaming_Datesurrendered,
    vwGamingLicences.LD_DateTransferred as Gaming_DateTransferred,
    vwGamingLicences.LD_DateSuspFrom as Gaming_DateSuspFrom,
    vwGamingLicences.LD_DateSuspTo as Gaming_DateSuspTo, 
    CASE WHEN vwGamingLicences.LD_Status='G' OR vwGamingLicences.LD_Status='SP'
		OR vwGamingLicences.LD_Status='A' THEN vwGamingLicences.LD_MachinesApproved 
		ELSE NULL END as Gaming_Machines,
	CASE WHEN vwGamingLicences.LD_Status='G' OR vwGamingLicences.LD_Status='SP' 
		THEN 'Number of Gaming Machines Approved:'
		WHEN vwGamingLicences.LD_Status='A'THEN 'Number of Gaming Machines Applied for:'
		ELSE '' END as Gaming_Machines_Text,
    LT_NormalDate, LT_MinorDate, LT_NormalInspector, LT_MinorInspector,
    Licences.LastupdateDatetime, Licences.LastupdateUser, Licences.TimeStamp    
FROM Licences 
	INNER JOIN LicenceNumbers ON Licences.LIC_LN_ID = LicenceNumbers.LN_ID
	LEFT JOIN vwGamingLicences ON Licences.LIC_ID = vwGamingLicences.LIC_ID
	LEFT JOIN vwLiquorLicences ON Licences.LIC_ID = vwLiquorLicences.LD_LIC_ID
	LEFT JOIN LicencePostalDetails ON Licences.LIC_LPD_ID = LicencePostalDetails.LPD_ID
	LEFT JOIN vwLicDetailsStatus ON vwLicDetailsStatus.LV_Value =vwLiquorLicences.LD_Status
	LEFT JOIN vwGamDetailsStatus ON vwGamDetailsStatus.LV_Value = vwGamingLicences.LD_Status
	LEFT JOIN PremisesNames ON Licences.LIC_PN_ID = PremisesNames.PN_ID
	LEFT JOIN LicenceTrips ON Licences.LIC_LT_ID = LicenceTrips.LT_ID
GO


