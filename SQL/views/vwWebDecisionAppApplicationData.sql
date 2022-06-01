USE [LGS_UAT]
GO

/****** Object:  View [dbo].[vwWebDecisionAppApplicationData]    Script Date: 9/12/2021 10:49:18 AM ******/
SET ANSI_NULLS ON
GO

SET QUOTED_IDENTIFIER ON
GO


/******************************************************************************
**		File: vwWebDecisionAppApplicationData.sql
**		Name: vwWebDecisionAppApplicationData
*******************************************************************************
**		Change History
*******************************************************************************
**		Date:		Author:		Description:
**		--------	--------	-----------------------------------------------
**		16/04/19    DWDEN/MXSAM	Created based on vwApplicationData
*******************************************************************************/

ALTER View [dbo].[vwWebDecisionAppApplicationData]
as

SELECT	
	JSON_VALUE(LSA_JSON, '$.info.LGO_Reference') LGO_Reference,
	Applications.APP_Id, 
	Applications.APP_AST_ID,
	Applications.APP_LC_ID,
	Applications.APP_LN_ID,
	Applications.APP_RC_ID,
	Applications.APP_LIC_ID,
	Applications.APP_ApplicNumber, 
	Applications.APP_Applicant, 
	Applications.APP_ReceiptDate, 
	Applications.APP_ReceiptCode, 
	Applications.APP_ApplicFee ,
	Applications.APP_ContactName, 
	Applications.APP_ContactAddress1, 
	Applications.APP_ContactAddress2, 
	Applications.APP_ContactTown, 
	Applications.APP_ContactPostCode, 
	Applications.APP_ContactPhone, 
	Applications.APP_ContactState, 
	Applications.APP_ContactFax, 
	Applications.APP_ContactEmail, 
	Applications.APP_HearingType,
	CASE Applications.APP_HearingType
		WHEN 'H' THEN 'H - Hearing'
		WHEN 'M' THEN 'M - Mention'
		WHEN 'J' THEN 'J - Judgement'
		WHEN 'C' THEN 'C - Call-Over'
		WHEN 'I' THEN 'I - Commissioners Inquiry'
	END HearingType, 
	Applications.APP_HearingAuthority,
	CASE Applications.APP_HearingAuthority
		WHEN 'C' THEN 'C - Commissioner'
		WHEN 'J' THEN 'J - Judge'
		WHEN 'I' THEN 'I - IGA'
	END HearingAuthority,
	CASE WHEN Applications.APP_HearingTime IS NOT NULL THEN
		cast(APP_HearingDate as date) ELSE NULL
	END as APP_HearingDate,
	Applications.APP_DecisionDate, 
	Applications.APP_Notes, 
	Applications.APP_ObjectDateLast, 
	Applications.APP_AdvertDateLast, 
	Applications.APP_GazDateLast, 
	CASE WHEN Applications.APP_HearingTime IS NOT NULL THEN
		cast(APP_HearingTime as time) ELSE NULL
	END as APP_HearingTime,
	Applications.APP_HearingEndTime,
	Applications.App_App_ID_Parent,
	Applications.App_AddressTo,
	Applications.APP_PO_ID,
	Applications.CreationUser,
	Applications.CreationDatetime, 
	Applications.LastUpdateUser, 
	Applications.LastUpdateDateTime, 
	Applications.APP_Exemption,
	LicenceClasses.LC_Desc, 
	SubString(CONVERT(VarChar(50),LicenceClasses.LC_Number),1,3) + ' ' + LicenceClasses.LC_Desc LC_ID ,
	LicenceNumbers.LN_Id, 
	CASE
		WHEN LicenceNumbers.LN_LicenceNumber IS NULL
		THEN (CASE WHEN LicenceNumbers_1.LN_LicenceNumber IS NULL THEN (SELECT TOP 1 LicenceNumbers2.LN_LicenceNumber FROM LicenceNumbers LicenceNumbers2 WHERE LicenceNumbers2.LN_LIC_ID = Licences.LIC_ID ORDER BY LicenceNumbers2.CreationDateTime) ELSE LicenceNumbers_1.LN_LicenceNumber END)
		ELSE   LicenceNumbers.LN_LicenceNumber
	END   LN_LicenceNumber,
	LicenceNumbers_1.LN_LicenceNumber APP_LN,
	LicenceNumbers_1.LN_LIC_ID LIC_ID_LN,
	Licences.LIC_PremisesPhone, 
	Licences.LIC_PremisesFax, 
	Licences.LIC_PremisesMobile, 
	Licences.LIC_PremisesEmail, 
	Licences.LIC_PremisesWeb, 
	Licences.LIC_PremisesAddress1, 
	Licences.LIC_PremisesAddress2, 
	Licences.LIC_PremisesPostcode, 
	Licences.LIC_PremisesState, 
	Licences.LIC_PremisesTown,
	Licences.LIC_PN_ID,
	Licences.LIC_ID,
	(SELECT COUNT(AO_ID) FROM ApplicationObjections WHERE AO_APP_ID = applications.APP_ID) ApplicationObjectionCount,
	PremisesNames.PN_Name, 
	ApplicationStreams.AS_Code,
	ApplicationStreams.AS_ID,
	ApplicationStreams.AS_Desc + ' ' + ApplicationStreams.AS_Code LStream, 
	ApplicationTypes.AT_ID,
	ApplicationTypes.AT_Group,
	ApplicationTypes.AT_Desc, 
	ApplicationTypes.AT_ShortDesc, 
	ApplicationTypes.AT_Prefix,
	ApplicationTypes.AT_Prefix + '  ' + ApplicationTypes.AT_ShortDesc AType, 
	ApplicationTypes.AT_Hearing, 
	ApplicationTypes.AT_Advertised, 
	ApplicationTypes.AT_Applicant, 
	ApplicationTypes.AT_AdditionalGamingMachines, 
	ApplicationTypes.AT_RelatedLicenceRecord,
	CASE when Applications.APP_ReceiptDate is null then '' else CONVERT(VARCHAR(12),Applications.APP_ReceiptDate,103) end +
		'-' + isnull(Applications.APP_ReceiptCode,'') AReciept, 
	'' CreateLicence, 
	ApplicationOrders.AOR_ResultDesc,
	ApplicationOrders.AOR_ResultCode,
	ApplicationOrders.AOR_OrdNo,
	ApplicationOrders.AOR_Date,
	ApplicationOrders.AOR_EffectiveDate,
	PoliceOfficers.PO_Name,
	IsNull(GamingApplications.GA_SellBuyEntitlementQty,0) GA_SellBuyEntitlementQty,
	CASE WHEN AT_BuySellEntitlements IN ('B','S','M') THEN 0 ELSE
	ISNULL(LicenceDetails.LD_MachinesApproved,0) END MachinesApproved,
	CASE WHEN AT_BuySellEntitlements IN ('B','S','M') THEN
	CASE WHEN AOR_ID IS NULL  THEN ISNULL(LicenceDetails.LD_MachinesApproved,0)
			ELSE IsNull(GamingApplications.GA_MachineQty,0)END 
	ELSE IsNull(GamingApplications.GA_MachineQty,0) END GA_MachineQty,
	CASE WHEN AOR_ID IS NULL THEN ISNULL((SELECT count(*) from vwEntitlements 
			where Status='Active' and EL_LIC_ID_Owner=Applications.APP_LIC_ID),0)
		ELSE IsNull(GamingApplications.GA_EntitlementsOwned,0)END Entitlements_Owned,
	CASE WHEN AOR_ID IS NULL THEN ISNULL((SELECT count(*) from vwEntitlements 
			where Status='Active' and EL_LIC_ID_Location=Applications.APP_LIC_ID),0)
		ELSE IsNull(GamingApplications.GA_EntitlementsLocated,0)END Entitlements_Located,
	ISNULL(RepresentativeContacts.RC_Firstname,'') + ' ' + ISNULL(RepresentativeContacts.RC_Surname,'') RepName, 
	RepresentativeContacts.RC_Firstname,
	RepresentativeContacts.RC_Surname, 
	RepresentativeContacts.RC_Email, 
	RepresentativeContacts.RC_Phone, 
	RepresentativeContacts.RC_Fax, 
	RepresentativeContacts.RC_Active,
	Representatives.REP_Name,
	(Select top 1 '*' from ApplicationObjections where AO_APP_ID=Applications.APP_ID) as Obj,
	EntBar.ENT_Address1 as BAR_Address1,EntBar.ENT_Address2 as BAR_Address2,
	EntBar.ENT_Town as BAR_Town,EntBar.ENT_State as BAR_State,
	EntBar.ENT_Postcode as BAR_PostCode,EntBar.ENT_Phone as BAR_Phone,
	EntBar.ENT_Fax as BAR_Fax,	EntBar.ENT_Mobile as BAR_Mobile,
	EntBar.ENT_Email as BAR_Email, BAR_Number,Applications.APP_BAR_ID,
	dbo.udfEntityName(EntDBar.ED_Surname,EntDBar.ED_Name1, EntDBar.ED_Name2, EntDBar.ED_Name3) as BAR_Name,
	APP_LA_ID, APP_LSC_ID, LA_Code, LSC_Code,
	AT_NoPublicAccesstoDO,
	ISNULL(APP_GRP_ID,'') APP_GRP_ID,
	APP_Incomplete,
	FA_AU_ID_AllocTo AS Allocated_AU_ID,
	AU_Name AS Allocated_AU_Name,
	APP_AdvInstructIssued,
	APP_OutstandingDocRec,
	APP_OutstandingDocRequested,
	APP_SapolAssessmentRequired,
	APP_SapolWishToIntervene,
	APP_CB_ID_SapolObjectReason,
	APP_SapolNotToIntervene,
	APP_SapolWishToInterveneUser,
	APP_SapolNotToInterveneUser,
	APP_SapolWishToInterveneUser as APP_SapolWishToInterveneUserRO,
	APP_SapolNotToInterveneUser as APP_SapolNotToInterveneUserRO,
	APP_Delegate_AU_ID,
	(Select AU_Name from AppUsers where AU_ID = APP_Delegate_AU_ID) as APP_Delegate_AU_Name,
	APP_CB_ID_FileStatus,
	CB_Code FileStatusCode,
	CB_Description FileStatusDesc,
	checksum(APP_HearingType, APP_HearingAuthority, APP_HearingDate, APP_HearingTime, APP_HearingEndTime, APP_Delegate_AU_ID) APP_Hearing_Checksum,
	checksum(FA_AU_ID_AllocTo,FA_AU_ID_AllocBy,FA_AllocatedDate,FA_CB_ID_Status) FA_Checksum,
	FA_AU_ID_AllocTo,
	(Select AU_Name from AppUsers where AU_ID = FA_AU_ID_AllocTo) as FA_AU_Name_AllocTo
FROM Applications
LEFT JOIN LicenceSystemApplications on Applications.APP_ID = LSA_APP_ID
LEFT JOIN ApplicationStreamTypes ON Applications.APP_AST_ID = ApplicationStreamTypes.AST_ID
LEFT JOIN ApplicationStreams ON ApplicationStreamTypes.AST_AS_ID = ApplicationStreams.AS_ID
LEFT JOIN Licences ON Applications.APP_LIC_ID = Licences.LIC_ID
LEFT JOIN PremisesNames ON Licences.LIC_PN_ID = PremisesNames.PN_ID
LEFT JOIN ApplicationTypes ON ApplicationStreamTypes.AST_AT_ID = ApplicationTypes.AT_ID
LEFT JOIN LicenceClasses ON Applications.APP_LC_ID = LicenceClasses.LC_ID
LEFT JOIN ApplicationOrders ON Applications.APP_ID = ApplicationOrders.AOR_APP_ID
LEFT JOIN LicenceNumbers ON Licences.LIC_LN_ID = LicenceNumbers.LN_ID
LEFT JOIN LicenceNumbers AS LicenceNumbers_1 ON Applications.APP_LN_ID = LicenceNumbers_1.LN_ID
LEFT JOIN PoliceOfficers ON Applications.APP_PO_ID = PoliceOfficers.PO_ID
LEFT JOIN GamingApplications ON Applications.APP_ID = GamingApplications.GA_APP_ID
LEFT JOIN RepresentativeContacts ON Applications.APP_RC_ID = RepresentativeContacts.RC_ID
LEFT JOIN Representatives ON RC_REP_ID=REP_ID
LEFT JOIN LicenceDetails ON (ApplicationStreamTypes.AST_AS_ID = LicenceDetails.LD_AS_ID AND Licences.LIC_ID = LicenceDetails.LD_LIC_ID)
--LEFT JOIN Applications AS Applications_1 ON Applications.App_App_ID_Parent = Applications_1.APP_ID     
LEFT JOIN Barrings on Applications.APP_BAR_ID=BAR_ID
LEFT JOIN Entities EntBar ON EntBar.ENT_ID = BAR_ENT_ID
LEFT JOIN EntityDetails EntDBar ON EntDBar.ED_ID = EntBar.ENT_ED_ID
LEFT JOIN LicenceAgents on Applications.APP_LA_ID=LA_ID
LEFT JOIN LicenceSubContractors on Applications.APP_LSC_ID=LSC_ID
LEFT JOIN FileAllocation ON FA_OBJ_ID = APP_ID AND FA_AS_ID = AST_AS_ID AND FA_OBJT_ID = 1
LEFT JOIN ComboBoxes ON CB_ID = APP_CB_ID_FileStatus
LEFT JOIN AppUsers on AU_ID = FA_AU_ID_AllocTo
WHERE (ApplicationOrders.AOR_Date = (SELECT MAX(ApplicationOrders.AOR_Date) FROM ApplicationOrders WHERE AOR_APP_ID = applications.APP_ID) OR ApplicationOrders.AOR_Date IS NULL)
GO


